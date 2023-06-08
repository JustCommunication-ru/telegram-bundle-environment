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
            // по умолчанию ключи числовые
            $_key = $i++;
            // Если указан массив ключей, то делаем составной: склейку через подчерк
            // Если название есть одно название ключа, то его значение будет ключом
            if (is_array($key)){
                $_key = implode("_",self::array_mask($row, $key));
            }elseif ($key!=''){
                if (strpos($key, '**')!==false){
                    $_key = mb_strtoupper($row[str_replace("*", "", $key)]);
                }elseif (strpos($key, '*')!==false){
                    $_key = mb_strtolower($row[str_replace("*", "", $key)]);
                }else{
                    if (!isset($row[$key])){
                        $trace = debug_backtrace();
                        $last_call_step = array_shift($trace);
                        throw new Exception('Not found key "'.$key.'" in array first argument of array_foreach() in '.(isset($last_call_step['file'])?$last_call_step['file']:'-').' on '.(isset($last_call_step['line'])?$last_call_step['line']:'-').'. Found only: '.implode(', ', array_keys($row)));
                    }
                    $_key = $row[$key];
                }
            }

            // Если val==true тогда возвращаем запись в первозданном виде
            // Иначе вытаскиваем только нужные поля (указан массив) поле (указана строка)
            if (is_bool($val) && $val){
                $_val = $row;
            }elseif (is_array($val)){
                $_val = array();
                foreach($val as $val_item){
                    $_val[$val_item]=$row[$val_item];
                }
            }else{
                $_val = $row[$val];
            }

            // Если попадаются две записи с одним ключом, то либо перезаписываем, либо делаем еще уровень и там уже по числовому ключу складываем
            if ($multi_key!==false){
                if ($multi_key===true){
                    $res[$_key][]=$_val;
                }elseif (isset($row[$multi_key])){
                    $res[$_key][$row[$multi_key]]=$_val;
                }
            }else{
                $res[$_key]=$_val;
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

}
