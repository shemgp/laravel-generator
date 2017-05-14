<?php

namespace InfyOm\Generator\Utils;

use Illuminate\Http\Request;

trait DatagridSortableAndFilterableUtil
{
    /**
     * Convert datagrid filters to repository filters.
     *
     * @param Request &$request
     */
    private function convertDatagridFilterToRepositoryFilter(Request &$request)
    {
        if (!isset($request['f'])) {
            return;
        }

        $f = $request['f'];

        if (isset($f['order_by']) && !empty($f['order_by'])
            && isset($f['order_dir']) && !empty($f['order_dir'])) {
            $request['orderBy'] = $f['order_by'];
            $request['sortedBy'] = $f['order_dir'] !== 'DESC' ? 'asc' : 'desc';
        }

        $searches = [];
        foreach ($f as $field => $value) {
            if ($value == '' || in_array($field, ['order_by', 'order_dir'])) {
                continue;
            }
            $f[$field] = trim($value);
            $searches[] = $field.':'.trim($value);
        }
        $request->merge(['f' => $f]);
        $request['search'] = implode(';', $searches);
        $request['searchUseAnd'] = 1;
    }

    /**
     * Set default order by and direction.
     *
     * @param Request $request
     */
    private function setDefaultOrder(&$request, $by, $dir = 'ASC')
    {
        if (!isset($request['f']['order_by']) ||
            isset($request['f']['order_by']) && $request['f']['order_by'] == '') {
            $request['orderBy'] = $by;
            $request['sortedBy'] = $dir;
        }
    }
}
