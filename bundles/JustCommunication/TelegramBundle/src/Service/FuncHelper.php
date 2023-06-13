<?php

namespace JustCommunication\TelegramBundle\Service;

// Syntactic Sugar

use Exception;

class FuncHelper
{

    // Превращает App\Controller\IndexCointroller в IndexCointroller
    static function baseClassName($string){
        $arr = explode('\\', $string);
        $res = array_pop($arr);
        return $res;
    }

    /**
     * Проекция среза массива полей $val на поле $key
     * Создает новый массив (если передано значение $key то ассоциативный по этому полю)
     * содержащий только поля/поле из $val
     * $key поддерживает преобразование полей одна звезда - в нижний регистр, две - в верхний
     *
     * @param ?array $arr массив
     * @param mixed $val строка или массив строк (названия полей) или true - в этом случае целиком (все поля)
     * @param string $key название поля который станет ключем в новом массиве, если не указан то возращаяется обынчый массив
     * @param bool $multi_key - если true то новые записи с одинаковым ключем соединяются в массив а не перезаписываются
     * @return array
     * @throws Exception
     */
    static public function array_foreach(?array $arr, $val, $key='', $multi_key=false): array{
        $res = array();
        $i=0;
        foreach ($arr as $row){

            if (is_array($row)) {
                // по умолчанию ключи числовые
                $_key = $i++;
                // Если указан массив ключей, то делаем составной: склейку через подчерк
                // Если название есть одно название ключа, то его значение будет ключом
                if (is_array($key)) {
                    $_key = implode("_", self::array_mask($row, $key));
                } elseif ($key != '') {
                    if (strpos($key, '**') !== false) {
                        $_key = mb_strtoupper($row[str_replace("*", "", $key)]);
                    } elseif (strpos($key, '*') !== false) {
                        $_key = mb_strtolower($row[str_replace("*", "", $key)]);
                    } else {
                        if (!isset($row[$key])) {
                            $trace = debug_backtrace();
                            $last_call_step = array_shift($trace);
                            throw new Exception('Not found key "' . $key . '" in array first argument of array_foreach() in ' . (isset($last_call_step['file']) ? $last_call_step['file'] : '-') . ' on ' . (isset($last_call_step['line']) ? $last_call_step['line'] : '-') . '. Found only: ' . implode(', ', array_keys($row)));
                        }
                        $_key = $row[$key];
                    }
                }

                // Если val==true тогда возвращаем запись в первозданном виде
                // Иначе вытаскиваем только нужные поля (указан массив) поле (указана строка)
                if (is_bool($val) && $val) {
                    $_val = $row;
                } elseif (is_array($val)) {
                    $_val = array();
                    foreach ($val as $val_item) {
                        $_val[$val_item] = $row[$val_item];
                    }
                } else {
                    $_val = $row[$val];
                }

                // Если попадаются две записи с одним ключом, то либо перезаписываем, либо делаем еще уровень и там уже по числовому ключу складываем
                if ($multi_key !== false) {
                    if ($multi_key === true) {
                        $res[$_key][] = $_val;
                    } elseif (isset($row[$multi_key])) {
                        $res[$_key][$row[$multi_key]] = $_val;
                    }
                } else {
                    $res[$_key] = $_val;
                }
            }elseif(is_object($row)){
                // здесь можно было бы вместо $row[$value] выполнять $row->getValue(); но много нюансов
                // э....
            }else{
                // э....
            }

        }
        return $res;
    }



    /**
     * Возвращает часть массива $arr по маске $keys, в порядке указанных ключей.
     * Пример: $arr=('id'=>5, 'name'=>'example', 'foo'=>10), $keys=('foo', 'id'). Результат:('foo'=>10, 'id'=>5)
     * @param array $arr - исходный массив
     * @param array $keys -  массив ключей которые нам нужны
     * @return array
     */
    static public function array_mask(array $arr, array $keys): array{
        $res = array();
        foreach ($keys as $key){
            if (isset($arr[$key])){
                $res[$key]=$arr[$key];
            }else{
                $res[$key]='';
            }
        }
        return $res;
    }
    /**
     * Удаляет из массива пустые элементы (два режима: для чисел и строк)
     * @param array $arr
     * @param string $type
     * @return array
     */
    static public function array_cleanup(array $arr, string $type='int'): array{
        $new_array = array();
        foreach ($arr as $val){
            if ($type=='int'){
                if ((int)$val>0){
                    $new_array[] = $val;
                }
            }elseif ($type=='string'){
                if ($val!=''){
                    $new_array[] = $val;
                }
            }
        }
        return $new_array;
    }

    /**
     * Сортирует массив по указанному полю
     * test:
     * $arr = array(
     * array('datein'=>2,'value'=>100),
     * array('datein'=>1,'value'=>200),
     * array('datein'=>3,'value'=>300),
     * );
     * print_r(Celib::getInstance('')->array_sort_by_field($arr, 'datein'));
     *
     * @param array $arr - исходный массив
     * @param string $field - название поля по которому сортировать
     * @param bool $save_keys - если планируется использовать foreach и нужны первоначальные не числовые! ключи
     * @return array
     */
    static public function array_sort_by_field(array $arr, string $field, bool $save_keys=false): array{
        $order =  strpos($field, " DESC")?SORT_DESC:SORT_ASC;
        $field = str_replace(array(" ASC", " DESC"), "", $field);

        $sort_arr = array();
        foreach($arr as $index=>$row){
            if ($save_keys){
                $arr[$index]['_array_sort_key']=$index;
            }
            // 2018-07-10 при сортировке опускаем в лоуверкейс, пример e-NV200 оказывался послесписка
            $sort_arr[$index]=  mb_strtolower($row[$field]);
        }
        array_multisort($sort_arr, $order,$arr);
        if ($save_keys){
            $new_arr = array();
            foreach ($arr as $row){
                $key = $row['_array_sort_key'];
                unset($row['_array_sort_key']);
                $new_arr[$key]=$row;
            }
            $arr=$new_arr;
        }
        return $arr;
    }

    /**
     * Преобразование стандартной mysql даты в php дату/время, по умолчанию в UNIX_TIMESTAMP
     * @date 2020-10-13 Теперь можно передавать резанную дату, без времени
     * @param string $date
     * @param string $format
     * @return string
     */
    static function dateDB(string $date, string $format="U"): string{
        if (strpos($date, " ")){
            $date_db_part = explode(" ", $date);
        }else{
            $date_db_part= array($date, '00:00:01');
        }
        $date_part = explode("-", $date_db_part[0]);
        $time_part = explode(":", $date_db_part[1]);
        $unix = mktime($time_part[0],$time_part[1],$time_part[2],(int)$date_part[1],(int)$date_part[2],(int)$date_part[0]);
        return date($format, $unix);
    }
}
