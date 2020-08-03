<?php
namespace App\Http\Traits;

use DB;

trait DBExtendedOperations {

    public static function batchUpdate( $data, $index=null, $where=null ) {
        $table = (new static)->getTable();
        $ids   = [];
        $where = ($where != '' AND count($where) >= 1) ? implode(" AND ", ((array) $where)).' AND ' : '';

        foreach ($data as $key => $val)
        {
            $ids[] = "'" . $val[$index] . "'";

            foreach (array_keys($val) as $field)
            {
                if ($field != $index)
                {
                    $final[$field][] =  'WHEN '.$index.' = \''.$val[$index].'\' THEN \''. str_replace("'", "\'", $val[$field]) . '\'';
                }
            }
        }

        $sql = "UPDATE ".$table." SET ";
        $cases = '';

        foreach ($final as $k => $v)
        {
            $cases .= $k.' = CASE '."\n";
            foreach ($v as $row)
            {
                $cases .= $row."\n";
            }

            $cases .= 'ELSE '.$k.' END, ';
        }

        $sql .= substr($cases, 0, -2);

        $sql .= ' WHERE '.$where.$index.' IN ('.implode(',', $ids).')';

        return DB::update( DB::raw($sql) );

    }
}
