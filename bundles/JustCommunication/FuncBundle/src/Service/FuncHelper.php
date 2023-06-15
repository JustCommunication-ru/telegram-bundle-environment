<?php

namespace JustCommunication\FuncBundle\Service;

// Syntactic Sugar

use Exception;

class FuncHelper
{
    private array $timers;

    // Превращает App\Controller\IndexCointroller в IndexCointroller
    static function baseClassName($string){
        $arr = explode('\\', $string);
        $res = array_pop($arr);
        return $res;
    }

    /**
     * Измеряем объем массива в элементарных единицах (простых типах)
     * @date 2020-07-03 Уыерен что уже писал до этого такую функцию
     * @param mixed $arr строго массив
     * @return int
     */
    static function array_volume(mixed $arr): int{
        $volume = 0;
        if (is_array($arr)){
            foreach ($arr as $el){
                $volume += is_array($el)?self::array_volume($el):1;
            }
        }else{
            $volume +=1;
        }
        return $volume;
    }

    /**
     * 2017-09-14 Это уже не array_search !!!
     * Индекс элемента массива по значению элемента, или значению ключа элемента
     * @param array $arr
     * @param mixed $val
     * @param mixed $key
     * @return mixed
     */
    static public function array_index(array $arr, mixed $val, $key=false): mixed{
        foreach ($arr as $k => $v){
            if ($key){
                if (isset($v[$key])&&$val==$v[$key]){
                    return $k;
                }
            }else{
                if ($val==$v){
                    return $k;
                }
            }
        }
        return false;
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

    static public function GetIP(): string{
        $stat_ip=array();
        if(isset( $_SERVER["HTTP_X_FORWARDED_FOR"]) ) {
            $str=explode(',',$_SERVER["HTTP_X_FORWARDED_FOR"]);
            foreach($str as $v) {$stat_ip[]=trim($v);}
        }
        if(isset( $_SERVER["REMOTE_ADDR"]) ) {
            $str=explode(',',$_SERVER["REMOTE_ADDR"]);
            foreach($str as $v) {$stat_ip[]=trim($v);}
        }
        return join('|',$stat_ip);
    }


    static function str_asterics($str, $visible_left_count=3, $visible_right_count=3){
        $asterisks_count = strlen($str)-$visible_left_count-$visible_right_count;

        return mb_substr($str, 0, $visible_left_count).($asterisks_count>0?str_repeat('*', $asterisks_count):'').mb_substr($str, -$visible_right_count, $visible_right_count);
    }


    /**
     * Проверка является ли текст номером мобильного телефона, clean уберет пробелы и слеши
     * Интересно: в javascript потребовалось обернуть выбор первой цифры в скобки, иначе рассуждает интерпритатор иначе
     * @param string $str
     * @param bool $clean
     * @return bool
    */
    static function isPhone(string $str, bool $clean=false): bool{
        if ($clean){
            $str = trim(str_replace(array(' ','-'), '', $str));
        }
        //+79024889090 79024889090 89024889090
        //if (!preg_match ( "/^[+]{0,1}[78][0-9]{10}$/", $str)) {
        if (!preg_match ( "/^(\+7|7|8){1}[0-9]{10}$/", $str)) {
            return false;
        }
        return true;
    }

    /**
     * Преобразование plain array в nested array
     * Подсмотрено в интернетах, афтар хитро вместо реурсивной обработки использует перестроение массива по ссылке
     * в плоском массиве должны быть ключи id, значение атрибута $parent_field_name должно ссылаться на такой id
     * вложенность будет осуществляться в поле $children_field_name
     * @param $source_arr - Plain array id->
     * @param string $parent_field_name
     * @param string $children_field_name
     * @return array
     */
    static function makeNested($source_arr, $parent_field_name='id_parent', $children_field_name='children'): array
    {
        $source = $source_arr; // копирует массив
        $nested = array();

        foreach ( $source as $k=> &$s ) {
            //echo 'item('.$k.'): ';
            //var_dump($s);

            if ( is_null($s[$parent_field_name]) || $s[$parent_field_name]==0) {
                // no parent_id so we put it in the root of the array
                $nested[] = &$s;
                //echo '-copy-'."\r\n";;
            }else {
                $pid = $s[$parent_field_name];
                if ( isset($source[$pid]) ) {
                    //echo '-found-'."\r\n";;
                    // If the parent ID exists in the source array
                    // we add it to the 'children' array of the parent after initializing it.
                    if ( !isset($source[$pid][$children_field_name]) ) {
                        $source[$pid][$children_field_name] = array();
                    }
                    $source[$pid][$children_field_name][] = &$s;
                }else{
                    //echo '-not_found-'."\r\n";
                }
            }
        }
        return $nested;
    }


    /*
    // прямо на месте можно использовать что-то типа этого. переделывает массив
    $tags = array_map(function($tag) {
        return array(
            'name' => $tag['name'],
            'value' => $tag['url']
        );
    }, $tags);
    */
    /**
     * Переименовывает ключи массива arr по карте map (до=>после, ...)
     * @param $arr - входной ассоц массив
     * @param $map - ассоциативный массив. ключи - ключи которые нужно взять/переименовать, значения - новые названия ключей
     * @param $copy_rest_key - надо ли копировать ключи не указанные в map
     * @return array
     */
    static function keyRename($arr, $map, $copy_rest_key=false): array
    {
        $new_arr = array();
        foreach($arr as $index => $element){
            $new_element = array();
            foreach ($element as $k=>$v){
                if (isset($map[$k])){
                    $new_element[$map[$k]] = $v;
                }elseif ($copy_rest_key){
                    // Если включен флаг копировать все остальные ключи
                    $new_element[$k] = $v;
                }else{
                    // игнорируем значение
                }
            }
            $new_arr[$index] = $new_element;

        }
        return $new_arr;

    }

    /**
     * @param int $size
     * @param int $after_zero
     * @param string $lang
     * @param string $round_to
     * @return string
     */
    static function size_converter(int $size, int $after_zero=3, string $lang='ru', string $round_to=''): string{

        $lang_arr = [
            'TB'=>['ru'=>' ТБ', 'en'=>' TB'],
            'GB'=>['ru'=>' ГБ', 'en'=>' GB'],
            'MB'=>['ru'=>' МБ', 'en'=>' MB'],
            'KB'=>['ru'=>' КБ', 'en'=>' KB'],
            'B'=> ['ru'=>' Б',  'en'=>' B'],
        ];
        if ($size/1024/1024/1024/1024>1 || $round_to=='TB'){
            return sprintf("%01.".$after_zero."f", $size/1024/1024/1024/1024).($lang_arr['TB'][$lang]??'');
        }elseif ($size/1024/1024/1024>1 || $round_to=='GB'){
            return sprintf("%01.".$after_zero."f", $size/1024/1024/1024).$lang_arr['GB'][$lang]??'';
        }elseif ($size/1024/1024>1 || $round_to=='MB'){
            return sprintf("%01.".$after_zero."f", $size/1024/1024).($lang_arr['MB'][$lang]??'');
        }elseif ($size/1024>1 || $round_to=='KB'){
            return sprintf("%01.".$after_zero."f", $size/1024).$lang_arr['KB'][$lang]??'';
        }else{
            return sprintf("%01.".$after_zero."f", $size).$lang_arr['B'][$lang]??' B';
        }
    }

    /**
     * Представление кучи секунд в привычнх днях,часах, минутах
     * @param int $ms
     * @param int $after_zero
     * @param string $lang
     * @return string
     */
    static function time_converter(int $ms, $delimeter=' ', string $lang='en'): string{
        /*
        // считаем на пальцах.Можно было пойти от остатков от деления снизу, можно делением сверху. пример:  156873
        $d = floor($ms/86400); // /60/60/24
        $h = floor(($ms-$d*86400)/3600); //60/60 //70473/3600=19
        $m = floor(($ms-$d*86400-$h*3600)/60);  //70473-68400=2073 /34
        $s = floor($ms-$d*86400-$h*3600-$m*60); // 33
        */

        $delimeters = array('d'=>86400000, 'h'=>3600000, 'm'=>60000, 's'=>1000, 'ms'=>1);
        //$caption_arr
        $cap_arr = array(
            'en'=>array('d'=>'d','h'=>'h','m'=>'m','s'=>'s','ms'=>'ms'),
            'ru'=>array('d'=>'д','h'=>'ч','m'=>'м','s'=>'с','ms'=>'мс')
        );

        //$res = array();
        $res_str_arr = array();
        $rest_ms = $ms;
        foreach ($delimeters as $key=>$d){
            $_res = floor($rest_ms/$d);
            //$res[$key] = $_res;
            if ($_res) {
                $res_str_arr[] = $_res . $cap_arr[$lang][$key] ;
            }
            $rest_ms -= $_res*$d;
        }

        return implode($delimeter, $res_str_arr);

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

    /**
     * Конвертация даты из нормального представления (12.03.2013/15:12:32) в бд (2013-03-28 15:12:32).
     * Строжайше проверяется.
     * На выходе всегда валидная дата
     * 2015-12-21 Убрано ограничение на год только не больше следующего, ибо привело к дикой ошибке (jccrm)
     *
     * @param string $date - строка с датой
     * @param string $time - строка с временем
     * @param string $format - формат (php) вывода сконвертированной даты
     * @param int $timeshift - сдвиг от полученного времени в секундах
     * @return string
     */
    static public function dateWeb(string $date, string $format="Y-m-d H:i:s", int $timeshift=0): string{
        // Зачистка пробелов - начало/конец -долой, в середине в один
        $date = preg_replace("/\s+/", '*',trim($date));

        //Сначала определяем есть ли в дате время
        if (strpos($date, ' ')>0){
            list($date, $time) = explode(' ',$date); // пробелов хоть лям срезать
        }else{
            $time="00:00:00";
        }

        $_date_arr = explode(".", $date, 3);
        $_time_arr = explode(":", $time, 3);

        // малополезная, но тем не менее нужная фича, дополняем нехватающие элементы
        if (count($_date_arr)==2){
            $_date_arr[2]=date("Y");
        }elseif (count($_date_arr)==1){
            $_date_arr[1]="01";
            $_date_arr[2]=date("Y");
        }
        // если подан только час:минута, то добавляем 00 секунд
        if (count($_time_arr)==2){
            $_time_arr[2]="00";
        }elseif (count($_time_arr)==1){
            $_time_arr[1]="00";
            $_time_arr[2]="00";
        }

        for ($i=0; $i<3; $i++){
            $_date_arr[$i] = (int) $_date_arr[$i];
            $_time_arr[$i] = (int) $_time_arr[$i];
        }

        if (count($_date_arr)==3 && count($_time_arr)==3 && // там по три элемента, инфа сотка
            $_date_arr[0] >= 1 && $_date_arr[0] <= 31
            && $_date_arr[1] >= 1 && $_date_arr[1] <= 12
            && $_date_arr[2] > 1970 && $_date_arr[2] <= 2070

            && $_time_arr[0] >= 0 &&  $_time_arr[0] <= 23
            && $_time_arr[1] >= 0 &&  $_time_arr[1] <= 59
            && $_time_arr[2] >= 0 &&  $_time_arr[2] <= 59
        ) {
            // присваеваем 100% рабочие данные
            //$selected_date = ((int) $_date_arr[2]) . '.' . (sprintf("%02d", (int) $_date_arr[1])) . '.' . (sprintf("%02d", (int) $_date_arr[0]));
            //$next_date = ((int) $_date_arr[2]) . '.' . (sprintf("%02d", (int) $_date_arr[1])) . '.' . (sprintf("%02d", (int) ($_date_arr[0] + 1)));
            //$selected_date_name = $_date_arr[0] . ' ' . $this->getMonthName($_date_arr[1]) . ' ' . ((int) $_date_arr[2]);

            $_timestamp=mktime((int)$_time_arr[0], (int)$_time_arr[1], (int)$_time_arr[2], (int)$_date_arr[1], (int)$_date_arr[0], (int)$_date_arr[2]);
            //$_date=date("Y-m-d H:i:s", $_timestamp);
        }else{
            //
            //$_date=date("Y-m-d H:i:s");
            // дефолт
            $_timestamp=date("U");
        }
        return date($format, $_timestamp+$timeshift);
    }

    /**
     * implode с возможностью обернуть элементы в cover
     * появилась возможность использовать вместо cover шаблон: '%value%=VALUES(%value%)'
     * @date 2017-11-07
     * @param string $glue
     * @param array $arr
     * @param string $cover
     * @param string $mode (addslashes, csv)
     * @return string
     */
    static function array_implode(string $glue, array $arr, string $cover='', string $mode=''): string{
        $new_arr = array();
        foreach ($arr as $key=>$el){
            if (str_contains($cover, '%value%') || str_contains($cover, '%key%')){
                $new_arr[]=str_replace(array('%value%', '%key%'), array(self::array_implode_convert($el, $mode), $key), $cover);
            }elseif ($cover!=''){
                $new_arr[]=$cover.self::array_implode_convert($el, $mode).$cover;
            }else{
                $new_arr[]=self::array_implode_convert($el, $mode);
            }
        }
        return implode($glue, $new_arr);
    }

    /**
     * Вспомогательная функция
     * @param string $el
     * @param string $mode
     * @return string
     */
    static function array_implode_convert(string $el, string $mode): string{
        return
            $mode=="addslashes"
                ?addslashes($el)
                :($mode=="csv"
                ?str_replace('"','""',$el)
                :($mode=="mysql"
                    ?str_replace(array('"',"'",'\\', '/'), '',$el)
                    :$el));
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


    /**+
     * @param string $name
     * @return string
     */
    static public function safeName(string $name): string{
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    }

    /**
     * Ссылка по которой к нам постучались. нужна для логов
     * @param string $brackets - строка из двух символов!
     * @return string
     */
    static public function getServerParamsStr($brackets='[]'): string{
        return (isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME'].'://':'')
            .($_SERVER['SERVER_NAME']??'')
            .($_SERVER['REQUEST_URI']??'') // Вроде как REQUEST_URI включает в себя QUERY_STRING, что странно
            //.(isset($_SERVER['QUERY_STRING'])&&$_SERVER['QUERY_STRING']!=''?'?'.$_SERVER['QUERY_STRING']:'')
            .' '.substr($brackets, 0, 1).($_SERVER['SERVER_PROTOCOL']??'').' '.($_SERVER['REQUEST_METHOD']??'').substr($brackets, 1, 1).' ' // скобки переменные
            .($_SERVER['REMOTE_ADDR']??'')
            .(isset($_SERVER['HTTP_X_FORWARDED_FOR'])&&$_SERVER['HTTP_X_FORWARDED_FOR']!=''?'|'.$_SERVER['HTTP_X_FORWARDED_FOR']:'');
    }

    /**
     * На вход функция принимает произвольное количесвто аргументов
     * формирует из списка элементов массив игнорируя пустые значения
     * todo может быть фильтровать нули еще с оглядкой на тип значения?
     * @return array
     */
    static public function  make_array(...$arg_list){
        //$arg_list = func_get_args();
        $list =[];
        foreach ($arg_list as $item){
            if ($item!=''){
                $list[]= $item;
            }
        }
        return $list;
    }

    /**
     * Перевод кириллицы в транслит
     * @param string $str
     * @param array $replace_map
     * @param string $chars
     * @return string
     */
    static function translitIt(string $str, array $replace_map=array(), string $chars = ''): string{
        // Все что ни есть буквы, цифры, пробелы - опускаем ниже плинтуса
        if (count($replace_map)>0){
            $str=str_replace(array_keys($replace_map),array_values($replace_map),$str);
        }
        $str = trim(preg_replace('/([^a-zа-я0-9\s'.$chars.']+)/u', '_', mb_strtolower($str, 'UTF-8')), '_');
        $tr = array(
            /*
            "А"=>"a","Б"=>"b","В"=>"v","Г"=>"g",
            "Д"=>"d","Е"=>"e","Ж"=>"j","З"=>"z","И"=>"i",
            "Й"=>"y","К"=>"k","Л"=>"l","М"=>"m","Н"=>"n",
            "О"=>"o","П"=>"p","Р"=>"r","С"=>"s","Т"=>"t",
            "У"=>"u","Ф"=>"f","Х"=>"h","Ц"=>"ts","Ч"=>"ch",
            "Ш"=>"sh","Щ"=>"sch","Ъ"=>"","Ы"=>"yi","Ь"=>"",
            "Э"=>"e","Ю"=>"yu","Я"=>"ya",
            */
            "а"=>"a","б"=>"b",
            "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
            "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
            "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
            "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
            "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
            "ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",

            " "=> "_",
            //"."=> "", "/"=> "_"
        );
        // делаем замену по массиву, повторяющиеся подчерки убираем
        return preg_replace('/([_]+)/', '_', strtr($str,$tr));
    }


    /**
     * Русификация даты, по умолчанию выводит в формате 23 марта 2023
     * Подробнее про формат можно найти здесь (он сильно отличается от формата date()):
     * https://unicode-org.github.io/icu/userguide/format_parse/datetime/
     * Пример формата с временем d MMMM y hh:mm:ss
     * @param \DateTime $date
     * @param $format
     * @return bool|string
     */
    static function dateTimeRu(\DateTime $date, $format='d MMMM y'){
        //$date = new \DateTime(date("Y-m-d H:i:s", date("U")-86400));
        $intlFormatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM, null, null, $format);
        return $intlFormatter->format($date);
    }

    /**
     * тоже что и dateTimeRu, но на основе $timestamp, можно было сделать один метод, но пока пробуем так
     * @param $timestamp
     * @param $format
     * @return bool|string
     * @throws \Exception
     */
    static function dateRu($timestamp, $format='d MMMM y'){
        $date = new \DateTime(date("Y-m-d H:i:s", $timestamp));
        $intlFormatter = new \IntlDateFormatter('ru_RU', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM, null, null, $format);
        return $intlFormatter->format($date);
    }

    /**
     * Создает случайную буквоциферную последовательность [0-9a-z] заданной длины
     * 2019-02-05 без I (!105) и O (!111);
     * @param int $length - длина строки
     * @return string - готовая строка
     */
    static public function randomStr(int $length): string{
        $arr = array();
        $str_arr = array();
        for($i=0; $i<$length; $i++){
            $arr[] = rand(1,2);
        }
        shuffle($arr);
        foreach($arr as $code){
            $str_arr[] = str_replace(array('i','o'),array('1','0'), chr($code==1?rand(48,57):rand(97,122)));
        }
        return implode("", $str_arr);
    }


}
