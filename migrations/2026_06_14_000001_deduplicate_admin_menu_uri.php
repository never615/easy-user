<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeduplicateAdminMenuUri extends Migration
{
    public function getConnection()
    {
        return config('admin.database.connection') ?: config('database.default');
    }

    public function up()
    {
        $menuTable = config('admin.database.menu_table');
        $roleMenuTable = config('admin.database.role_menu_table');

        if (!Schema::hasTable($menuTable)) {
            return;
        }

        DB::table($menuTable)
            ->where('uri', '')
            ->update(['uri' => null]);

        DB::transaction(function () use ($menuTable, $roleMenuTable) {
            $duplicateUris = DB::table($menuTable)
                ->select('uri')
                ->whereNotNull('uri')
                ->groupBy('uri')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('uri');

            foreach ($duplicateUris as $uri) {
                $menus = DB::table($menuTable . ' as menu')
                    ->leftJoin($menuTable . ' as child', 'child.parent_id', '=', 'menu.id')
                    ->select('menu.id', DB::raw('COUNT(child.id) as child_count'))
                    ->where('menu.uri', $uri)
                    ->groupBy('menu.id')
                    ->orderByRaw('COUNT(child.id) DESC')
                    ->orderByDesc('menu.id')
                    ->get();

                if ($menus->count() < 2) {
                    continue;
                }

                $keepId = $menus->first()->id;
                $deleteIds = $menus->pluck('id')
                    ->reject(static fn($id) => (int)$id === (int)$keepId)
                    ->values()
                    ->all();

                DB::table($menuTable)
                    ->whereIn('parent_id', $deleteIds)
                    ->update(['parent_id' => $keepId]);

                if (Schema::hasTable($roleMenuTable)) {
                    DB::table($roleMenuTable)
                        ->whereIn('menu_id', $deleteIds)
                        ->update(['menu_id' => $keepId]);

                    DB::statement("
                        DELETE FROM {$roleMenuTable} a
                        USING {$roleMenuTable} b
                        WHERE a.ctid > b.ctid
                          AND a.role_id = b.role_id
                          AND a.menu_id = b.menu_id
                    ");
                }

                DB::table($menuTable)
                    ->whereIn('id', $deleteIds)
                    ->delete();
            }
        });

        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS admin_menu_uri_unique
            ON {$menuTable} (uri)
            WHERE uri IS NOT NULL
        ");
    }

    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS admin_menu_uri_unique');
    }
}
