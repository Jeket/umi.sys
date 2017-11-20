 <?
 function setHeaderPicFromContent(){
	//Вставка первой картинки из контента в header_pic
	 //берем страницу
	include "standalone.php";
	include $_SERVER["DOCUMENT_ROOT"] . "/templates/sysLab-soltec/php/functions.php";
	echo '<meta charset="utf-8">';
	$pages = new selector('pages');
	foreach ($pages as $page) {
	    if($page->getValue('content')){
	        $content = $page->getValue('content');
	    }else{
	        $content = $page->getValue('top_txt');  
	    }
	    //находим первую картинку в контенте
	    $src = getImgSrc($content);
	    $img = new umiImageFile( "." . $src);
	    //print_r($img);
	    //Пихаем её в картинку активного раздела
	    // Работай, мразь ты тупая, заклинаю тебя во имя Аллаха
	    $page->setValue('header_pic', array($img));
	    $page->commit();
	    if($page->setValue('header_pic', array($img))){
	        echo "Для страницы <b>".$page->getName()."</b> установлено изображение header_pic<br>";
	    }else{
	        echo "Для страницы <b>".$page->getName()."</b> <span color='red'>не установлено изображение header_pic</span><br>";
	    }
	}
}
function ContentHtmlscecialchars(){
	//Замена htmlspecialchars в контенте всех страниц сайта
	 include "functions.php";
	echo '<meta charset="utf-8">';
	$pages = new selector('pages');
	foreach ($pages as $page) {
	   $id = $page->id;   
	   $new_page = $hierarchy->getElement($id);
	   $content=$new_page->getValue('content');
	   //$new_content =;
	   $new_page->setValue('content',  htmlspecialchars_decode($content));
	   $new_page->commit();
	   $old_mode = umiObjectProperty::$IGNORE_FILTER_INPUT_STRING;
		umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = true;
	   $new_content = $new_page->getValue('content');
	   echo $new_content;
	   //unset ($new_content);
	   unset($new_page);
	}
}
function setIsVisibleCategory($category_path, $isVisible){
	//Сделать все страницы контента категории видимыми\не видимыми для меню
	//$category_path принимает путь категории от корня
	//$isVisible - флаг видимости, который надо установить (bool)
	include "standalone.php";
	echo '<meta charset="utf-8">';
	$hierarchy = umiHierarchy::getInstance();
	$elementId = $hierarchy->getIdByPath($category_path);
	$pages = new selector('pages');
	$pages->where('hierarchy')->page($elementId)->childs(1);
	$ne = 'не';
	if($isVisible){
		$ne = '';
	}
	foreach ($pages as $page) {
	    $type = $page->getHierarchyType();
	    if($type->getTitle() == 'Страницы контента'){
	        $page->setIsVisible($isVisible);
	        echo "Странца <b>{$page->getName()}</b> теперь {$ne} видима для меню<br>";
	    }
	    // echo "<b>{$page->getName()}</b> имеет тип <b>{$type->getTitle()}</b><br>";
	    // $childs_id = $hierarchy->getChildrenList($page->getId(), false);
	    // foreach ($childs_id as $child_id) {
	    //     $child = $hierarchy->getElement($child_id);
	    //     $child->setIsVisible(true);
	    //     echo "<b>{$child->getName()}</b> =>> видима для меню<br>";
	    //  } 
	}
}
function getImgObjArray($catalog){
	// Формируем массив объектов umiImageFile из каталога $catalog
	//возвращает массив объектов umiImageFile
	 error_reporting(E_ALL);
	 include "standalone.php";
	 $curr_dir = CURRENT_WORKING_DIR;
	function recursiveGlob($pattern = '*', $flags = 0, $path = '') {
	    $paths = glob($path . '*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
	    $files = glob($path . $pattern, $flags);
	    foreach ($paths as $path) { 
	        $files=array_merge($files, recursiveGlob($pattern, $flags, $path)); 
	    }
	    return $files;
	 }
	$files = recursiveGlob('*', 0, $curr_dir.$catalog);
	foreach ($files as $file) {
	    $file = new umiImageFile($file);
	    $images[] = $file;
	}
	return $images;
}
function setCategoryTemplate($tpl_id){
	//Пакетное назначение шаблонов $tpl_id категориям с детьми не товарами
	include "standalone.php";
	include $_SERVER["DOCUMENT_ROOT"] . "/templates/sysLab-soltec/php/functions.php";
	echo '<meta charset="utf-8">';
	$umiTypesHelper = umiTypesHelper::getInstance();
	$template = templatesCollection::getInstance();
	$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'object');
	$pages = new selector('pages');
	foreach ($pages as $page) {
	    $current_page_id = $page->getId(); 
	   // получаем id текущей страницы
	   $hierarchy = umiHierarchy::getInstance(); 
	   // получаем экземпляр коллекции
	   $prod_childs = $hierarchy->getChildrenCount($current_page_id, false, true, 1, $hierarchyTypeId); 
	   $childs = $hierarchy->getChildrenCount($current_page_id, false, true, 1); 
	   // Взять страницы с детьми не товарами
	   if($prod_childs == 0 & $childs > 0){
	    // Назначить шаблон
	        $page->setTplId($tpl_id);
	        $tpl = $template->getTemplate($tpl_id);
	        echo "Странице <b>{$page->getName()}</b> присвоен шаблон вывода  <b>{$tpl->getTitle()}</b><br>";
	   }
	}
}

function commentTable(){
	//Закомментировать таблицы в контенте для родителей не товаров
	 error_reporting(E_ALL);
	 include "standalone.php";
	 echo '<meta charset="utf-8">';
	$umiTypesHelper = umiTypesHelper::getInstance();
	$hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'object');
	$hierarchy = umiHierarchy::getInstance(); 
	// Выбрать страницы с детьми не товарами
	 $pages = new selector('pages');
	foreach ($pages as $page) {
	    $current_page_id = $page->getId(); 
	    $prod_childs = $hierarchy->getChildrenCount($current_page_id, false, true, 1, $hierarchyTypeId); 
	    $childs = $hierarchy->getChildrenCount($current_page_id, false, true, 1);
	    if($prod_childs == 0 & $childs > 0){
	        // Взять контент
	        $content = ($page->getValue('content')) ? $page->getValue('content') : $page->getValue('top_txt');
	        // Закомментить таблицы
	        $content = str_replace('<table', '<!--<table', $content);
	        $content = str_replace('</table>', '</table>-->', $content);
	        // Записать контент обратно
	        umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = true;
	        $page->getValue('content') ? $page->setValue('content', $content) : $page->setValue('top_txt', $content);
	        echo "На странице <b>{$page->getName()}</b> закомментированы таблицы<br>";
	    }
	}
}
//парсинг таблицы и запись данных в соответствующие поля товаров
//Со страницы категории таблица парсится и данные заносятся в свойства товаров этой категории
//$category принимает строковое значение alt_name категории
//$columns если 0  - не записывать данные, если не 0 - столбцы, данные из которых записывать
//$table_direction: matrix - таблица данных реализована матрицей
//$content_from: 0 - парсим страницу категории, 1 - страницы товаров
//$setProdObj - 0 - не заменять тип данных продукта. Если передан ID-объекта - заменится объект данных
function loadDataTableFromContent($category, $columns = 0, $table_direction = 0, $content_from = false, $setProdObj = 0){
    error_reporting(E_ALL);
    include "standalone.php";
    include "phpQuery-onefile.php";
    include $_SERVER["DOCUMENT_ROOT"] . "/templates/sysLab-soltec/php/functions.php";
    // include "parser.php";
    echo '<meta charset="utf-8">';
    function compareStrings($str1, $str2) {
        return 100 * (
                similar_text($str1, $str2) / (
                        (strlen($str1) + strlen($str2))
                        / 2)
        );
    }
    function getAssocArray($propNames, $values){
        $values = pq($values)->find('td')->get();
        $props['table_name'] = $values[0]->nodeValue;
        for($i=1;$i <count($propNames);$i++){
            $props[$propNames[$i]->nodeValue] = $values[$i]->nodeValue;
        }
        return  array($props['table_name'], $props);
    }
    function getObjFromTable($tr){ //Парсим данные из строки в объект
         $obj['table_name'] = pq($tr)->find('td:first')->text();
         $obj['obem'] = pq($tr)->find('td:nth-child(2)')->text();
         $obj['mownost'] = pq($tr)->find('td:nth-child(3)')->text();
         $obj['razmery_emkosti'] = pq($tr)->find('td:nth-child(4)')->text();
         $obj['price'] = preg_replace('/[^0-9\,]/', '', pq($tr)->find('td:nth-child(5)')->text());
         $obj['mownost'] = str_ireplace(',', '.', $obj['mownost']);
        /* ?><pre>Объект из строки таблицы: <?var_dump($obj)?></pre><?*/
        return $obj;
        
    }
    function RUStoLAT($str){
        $mapping = array(
            'Н' => 'H'
        );
        foreach ($mapping as $rus => $lat) {
            $str = str_replace($rus, $lat, $str);
        }
        return $str;
    }
    function similar($finded_id, $name){
        $hierarchy = umiHierarchy::getInstance();
        $max = 0;
        $that_id = 0;
        foreach ($finded_id as $oneofId) {
            // echo "{$oneofId}<br>";
            $similarity = compareStrings($name, $hierarchy->getElement($oneofId)->getName());
            // echo "Схожесть {$name}  и {$hierarchy->getElement($oneofId)->getName()} - {$similarity}<br>";
            if($similarity > $max){
                $max = $similarity;
                $that_id = $oneofId;
            }
        }
        return $that_id;
    }
    $umiTypesHelper = umiTypesHelper::getInstance();
    $hierarchyTypeId = $umiTypesHelper->getHierarchyTypeIdByName('catalog', 'object');
    $objects = umiObjectsCollection::getInstance();
    $pages = new selector('pages');
    $pages->where('alt_name')->equals($category);
    // var_dump($pages->length);
    foreach ($pages as $page) {
        $current_page_id = $page->getId(); 
        $prod_childs = $hierarchy->getChildrenCount($current_page_id, false, true, 1, $hierarchyTypeId); 
        $childs = $hierarchy->getChildrenTree($current_page_id);
        //Берем контент
        if(!$content_from){
            $content = $page->getValue('top_txt').$page->getValue('bottom_txt');
            $content = phpQuery::newDocumentHTML($content);
            $propNames = $content->find('table')->find('tr:first')->find('td')->get();
        }else{
            // $xml = simplexml_load_string($_SERVER["DOCUMENT_ROOT"] . "/templates/sysLab-soltec/php/wp.xml");
            // $json = json_encode($xml);
            // $array = json_decode($json,TRUE);//TODOдоделать
            // // $child_page->altName =  str_replace(' rastvoritelyah', 'rastvoritelyax', $child_page->altName);
            // // $url = $content_from.$child_page->altName;
            // // echo "алиас: {$child_page->altName}<br>";
            // // $file = file_get_contents($url);
            // $doc = phpQuery::newDocumentHTML($file);
            // $content = pq($doc)->find('#content');
        }
        //Разбираем в массив данных
        if($content){
            // echo "Контент есть<br>";
            $content = phpQuery::newDocumentHTML($content);
            /*?><pre>$table_direction: <?var_dump($table_direction)?></pre><?*/
            foreach ($content->find('table')->find('tr:not(:first)') as $tr) {
                /*?><pre>Строка таблицы: <?var_dump($tr)?></pre><?*/
                if($table_direction == 'matrix'){
                    /* ?><pre>$tr: <?var_dump(pq($tr)->text())?></pre><?*/  
                    $obj = getObjFromTable($tr);
                    // $obj[$propName] = $propVal;
                    /*?><pre>$obj: <?var_dump($obj)?></pre><?*/
                }else{
                    $obj['table_name'] = pq($tr)->find('td:first')->text();
                    $obj['razmery_emkosti'] = pq($tr)->find('td:nth-child(4)')->text();
                    // list($propName, $propVal) = getAssocArray($propNames, $tr);
                    // $obj[$propName] = $propVal;
                }
               /*?><pre>$obj: <?var_dump($obj)?></pre><?*/
                $products = new selector('pages');
                $products->where('name')->equals($obj['table_name']);
                // echo "{$products->length}<br>";
                $that_id = 0;

                if(count($products->result()) > 0){
                    $that_id = $products->result()[0]->getId();
                    $similar_descr = "<b style='color:green'>(точно)</b>";
                }else{
                    //Если нет имени - Пробегаем по детям и сравниваем имена
                    // echo "Нет точного совпадения имени - Пробегаем по детям и сравниваем имена<br>";
                    $finded_id = [];
                    $name = preg_replace ("/[^a-zA-ZА-Яа-я0-9]/","",$obj['table_name']);
                    foreach ($childs as $child_id => $tmp) {
                        $child_page = $hierarchy->getElement($child_id);
                        $child_obj = $child_page->getObject();
                        if($setProdObj & $child_obj->getTypeId() !== $setProdObj){
                            $child_page->setObject($child_obj);
                            $child_obj->setTypeId($setProdObj);
                            $child_page->commit();
                            $child_page->update();
                            $child_obj->update();
                        }
                        $prod_name = $child_page->getName();
                        $prod_name = preg_replace ("/[^a-zA-ZА-Яа-я0-9]/","",$prod_name);
                        $prod_name = RUStoLAT($prod_name);
                        $name = htmlspecialchars_decode($name);
                        // $similarity = levenshtein($name, $prod_name, 10, 5, 1); //inst, rep, del
                        // echo "{$prod_name} / {$name}<br>";
                      /*  ?><pre><?var_dump(strripos($prod_name, $name))?></pre><?*/
                        if (!(strripos($prod_name, $name) === false)) {
                            // echo "Есть совпадение<br>";
                            $finded_id[] = $child_id;
                        }else{
                            // echo "Ищем <b>{$name}</b> в <b>{$prod_name}</b>:<br>";
                        }

                        // if (count($finded_id == 1)) {
                        //     $that_id = $finded_id[0];
                        // }else{
                        // }
                    }
                    // echo "{$name} =>";
                    $similar_descr = '';
                    if (empty($finded_id)) {
                        $that_id = similar(array_keys($childs), $name);
                        // var_dump($that_id);
                        $similar_descr = "<span style='color:red'> (отдаленно похожие)</span>";
                        echo "{$similar_descr}<br>";
                    }elseif (count($finded_id) > 1) {

                        $that_id = similar($finded_id, $name);
                        $similar_descr = "<b style='color:orange'>(выбрано из похожих)</b>";
                        // echo "{$similar_descr}<br>";
                    } else {
                        $that_id = $finded_id[0];
                        $similar_descr = "<b style='color:blue'>(приведенное)</b>";
                        // echo "{$similar_descr}<br>";
                    }

                }
                if ($hierarchy->getElement($that_id)) {
                    $finded_prod = $hierarchy->getElement($that_id);
                    $finded_prod_name = $finded_prod->getName();
                    echo "Строка <b>{$obj['table_name']}</b> соответствует товару <b>{$finded_prod_name}</b> {$similar_descr}, значения:<br>";
                    ?><pre><?var_dump($obj)?></pre><?

                        
                        in_array(2, $columns) ? $finded_prod->setValue('obem', $obj['obem']) : false;
                        in_array(3, $columns) ? $finded_prod->setValue('mownost', $obj['mownost']) : false;
                        in_array(4, $columns) ? $finded_prod->setValue('razmery_emkosti', $obj['razmery_emkosti']) : false;
                        in_array(5, $columns) ? $finded_prod->setValue('price', $obj['price']) : false;
                    // echo number_format($price, 2, ',', ' ')."<br>";
                }
                
            }
        } 
    }
}

?>