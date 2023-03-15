<?php 
if (!defined('_PS_VERSION_'))
    exit;


class perfect_productbanner extends Module{

//------------------------------------------------------------------
//Instalacja, odinstalowywanie - konfiguracja wstępna
//------------------------------------------------------------------

	public function __construct()
	{
		$this->name = 'perfect_productbanner'; /* This is the 'technic' name, this should equal to filename (mycustommodule.php) and the folder name */
		$this->author = 'Damian Dudek'; /* I guess it was clear */
		$this->version = '1.0.0'; /* Your module version */
		$this->need_instance = 0; /* If your module need an instance without installation */

		$this->ps_versions_compliancy = [ /* Your compatibility with prestashop(s) version */
			'min' => '1.7.1.0',
			'max' => _PS_VERSION_,
		];

		$this->bootstrap = true; /* Since 1.6 the backoffice implements the twitter bootstrap */
		parent::__construct(); /* I need to explain that? */

		$this->displayName = $this->l('Product banners'); /* This is the name that merchant see */
		$this->description = $this->l('Product banners - opis.'); /* A short description of functionality of this module */

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?'); /* This is a popup message before the uninstalling of the module */
	}

	public function install()
	{
		if (Shop::isFeatureActive())
		{
			Shop::setContext(Shop::CONTEXT_ALL);
		}
		$this->createTable();

		//Instalacja, inicjacja danych początkowych oraz podpięcie hook'ów do wyświetlania danych na stronie
		if (!parent::install() ||
			!$this->registerHook('displayAfterProductThumbs') ||
            !$this->registerHook('displayProductBannerTop') ||
            !$this->registerHook('displayRightColumnProduct') ||
			!Configuration::updateValue('PERFECT_PRODUCTBANNER_STRING', 'Pictograms') ||
			!Configuration::updateValue('PERFECT_PRODUCTBANNER_WIDTH', '40px') ||
			!Configuration::updateValue('PERFECT_PRODUCTBANNER_HEIGHT', 'auto') ||
            !Configuration::updateValue('PERFECT_PRODUCTBANNER_RIGHT', 0) ||
			!Configuration::updateValue('PERFECT_PRODUCTBANNERR_BOT', 0) ||
			!Configuration::updateValue('PERFECT_PRODUCTBANNER_TOP', 0)
			)
			return false;
		return true;
	}


	public function uninstall(){
		if (!parent::uninstall() ||
		!$this->unregisterHook('displayAfterProductThumbs') ||
        !$this->unregisterHook('displayProductBannerTop') ||
        !$this->unregisterHook('displayRightColumnProduct') ||
		!Configuration::deleteByName('PERFECT_PRODUCTBANNER_STRING') ||
		!Configuration::deleteByName('PERFECT_PRODUCTBANNER_WIDTH') ||
		!Configuration::deleteByName('PERFECT_PRODUCTBANNER_HEIGHT') ||
        !Configuration::deleteByName('PERFECT_PRODUCTBANNER_RIGHT') ||
		!Configuration::deleteByName('PERFECT_PRODUCTBANNER_TOP') ||
		!Configuration::deleteByName('PERFECT_PRODUCTBANNER_BOT')
		)
			return false;//Jeżeli anulowano odinstalowanie
		
		$this->dropTable();//Usuwanie tabeli bazy danych podczas odinstalowania - usunąć jeżeli po odinstalowaniu tabela ma pozostać w bazie
		return true;
	}


//------------------------------------------------------------------
//Wyświetlanie po stronie ADMINA
//------------------------------------------------------------------


	//Strona konfiguracji w panelu admina
	public function getContent(){
		//Po Submit - Usuń wiele wierszy jednocześnie
		if(Tools::isSubmit('submitBulkdelete_perfect_productbanner')){
			$pictogramstodelete = Tools::getValue('_perfect_productbannerBox');
			foreach ($pictogramstodelete as $key => $value) {
				$this->removeRow($value);
			}
		}
		//Po Submit - Zapisanie zmiennych w bazie - po przycisku zapisz jednego z formularzy
		if(Tools::isSubmit('submit_save_settings'))
		{
			Configuration::updateValue('PERFECT_PRODUCTBANNER_STRING',Tools::getValue('PERFECT_PRODUCTBANNER_STRING'));
			Configuration::updateValue('PERFECT_PRODUCTBANNER_WIDTH',Tools::getValue('PERFECT_PRODUCTBANNER_WIDTH'));
			Configuration::updateValue('PERFECT_PRODUCTBANNER_HEIGHT',Tools::getValue('PERFECT_PRODUCTBANNER_HEIGHT'));
		}

		//Dodanie skryptu js do panelu admina
		$script = '<script src="'.$this->_path.'views/js/config.js"></script>';
		
		$output = null;
		//Po Submit - Zapisanie zmiennych w bazie - po przycisku zapisz jednego z formularzy
		if(Tools::isSubmit('submit_save_slide'))
		{
			//$cats = Category::getCategories( (int)($cookie->id_lang), true, false  ) ;
			//print_r($cats);

			//Zapis zdjęcia w folderze
			$image_name = strval(Tools::getValue('PERFECT_PRODUCTBANNER_NAME'));
			$height = Configuration::get('PERFECT_PRODUCTBANNER_HEIGHT');
			$width = Configuration::get('PERFECT_PRODUCTBANNER_WIDTH');
			$catsel = Tools::getValue('cat_select')[0];
			$position = Tools::getValue('options')[0];
			if(!$image_name || empty($image_name) || !Validate::isGenericName($image_name)) //isGenericName sprawdza czy nazwa jest odpowiednia
				$output .= $this->displayError($this->l('Name incorrect'));
			else
			{
				$imageurl = $this->postFiles($image_name);
				if(!$imageurl)
					$output .= $this->displayError($this->l('Invalid image file'));
				else
				{
					//Zapis linku do obrazka w bazie danych
					$this->insertImageUrl($imageurl,$image_name,$catsel,$height,$width,$position);
					$output .= $this->displayConfirmation($this->l('Settings updated'));
				}
			}
            
		}

		//Po Submit - Jeżeli 2 parametry są ustawione, to należy usunąć wpis ze Slide'em z bazy danych
		if(Tools::isSubmit('submit-remove') && !empty(Tools::getValue('to-remove')))
		{
			$this->removeRow(Tools::getValue('to-remove'));
		}

		//Wyświetl w panelu admina stronę z połączonych elementów
		return $output.$this->displayForm().$this->renderList().$this->diplayModal().$script;
	}
	
	
	//Formularz (2 formularze w tym przypadku) w panelu admina
	public function displayForm(){

		// Get default language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		// Init Fields form array
		$fields_form = array();
		$fields_form[0]['form'] = array(
		'legend' => array(
			'title' => $this->l('Settings')
		),
		'input' => array(
			array(	//Lista tagów i wartości odpowiada tym ze zwykłego inputa
				'type' => 'text',
				'label' => $this->l('Set inscription'),
				'name' => 'PERFECT_PRODUCTBANNER_STRING'
			),
			array(
				'type' => 'text',
				'label' => $this->l('Set the width for all pictograms (in pixels, percentages etc.)'),
				'name' => 'PERFECT_PRODUCTBANNER_WIDTH'
			),
			array(
				'type' => 'text',
				'label' => $this->l('Set the height for all pictograms (in pixels, percentages etc.)'),
				'name' => 'PERFECT_PRODUCTBANNER_HEIGHT'
			)
					   
		),// --- end of input

		'submit' => array(
			'title' => $this->l('Save'),
			'class' => 'btn btn-default pull-right',
			'name' => "submit_save_settings"
		)
		);
		$fields_form[1]['form'] = array(
		'legend' => array(
		  'title' => $this->l('Create pictogram')
		),
		'input' => array(
			array(
				'type' => 'file',
				'label' => $this->l('Image upload'),
				'name' => 'perfect_productbanner',
				'required' => true
			),
			array(
				'type' => 'text',
				'label' => $this->l('Set name'),
				'name' => 'PERFECT_PRODUCTBANNER_NAME',
				'required' => true
            ),
            array(
                'type' => 'radio',
                'label' => $this->l('Disp banner'),
				'required' => true,
                'name' => 'options[]',
                'values' => $options = array(
                      array(
                          'id_banner' => 'PERFECT_PRODUCTBANNER_TOP' ,
                          'label' => $this->l('Top'),
                          'value' => 1
                      ),
                      array(
                          'id_banner' => 'PERFECT_PRODUCTBANNER_BOT',
                          'label' => $this->l('Bot'),
                          'value' => 2
                      ),
                      array(
                          'id_banner' => 'PERFECT_PRODUCTBANNER_RIGHT',
                          'label' => $this->l('Right'),
                          'value' => 3
                      ),
                  ),
            ),

			array(
				'type' => 'select',
				'label' => $this->l('Category:'),
				'name' => 'cat_select[]',
				'required' => true,
				'options' => array(
				  'query' => $this->getCategory(),
				  'id' => 'id',
				  'name' => 'name'
				)
			  ),
			 
			),// --- end of input

			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'btn btn-default pull-right',
				'name'=>'submit_save_slide'
			)
		);
		//Konfiguracja formularza ze zdefiniowanymi w $fields_form polami
		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;	//requires the instance of the module that will use the form
		$helper->name_controller = $this->name;	//requires the name of the module
		$helper->token = Tools::getAdminTokenLite('AdminModules');	//requires a unique token for the module. getAdminTokenLite() helps us generate one.
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;	//It's the url of the current page

		// Language
		$helper->default_form_language = $default_lang;	//requires the default language for the shop
		$helper->allow_employee_form_lang = $default_lang;	//requires the default language for the shop

		// Title and toolbar
		$helper->title = $this->displayName;	//requires the title for the form - Tutaj podana wartość nazwy modułu
		$helper->show_toolbar = true;        // false -> remove toolbar (toolbar displayed or not)
		$helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen. (toolbar always visible when scrolling or not.)
		$helper->submit_action = 'submit'.$this->name;	//requires the action attribute for the form’s <submit> tag - akcja przypisana do kliknięcia submit
		$helper->toolbar_btn = array(	//buttons that are displayed in the toolbar. In example, the “Save” button and the “Back” button.
			'save' =>
			array(
			  'desc' => $this->l('Save'),
			  'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
			  '&token='.Tools::getAdminTokenLite('AdminModules'),
			),
			'back' => array(
			  'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
			  'desc' => $this->l('Back to list')
			)
		);
		
		//Load current value - Uzupełnianie kolejnych inputów wartościami do wyświetlenia
		$helper->fields_value = array(	//define the value of the named tag
		'PERFECT_PRODUCTBANNER_STRING' => Configuration::get('PERFECT_PRODUCTBANNER_STRING'),
		'PERFECT_PRODUCTBANNER_WIDTH' => Configuration::get('PERFECT_PRODUCTBANNER_WIDTH'),
		'PERFECT_PRODUCTBANNER_HEIGHT' => Configuration::get('PERFECT_PRODUCTBANNER_HEIGHT'),
		);
		return $helper->generateForm($fields_form);
	}

	//Lista slide'ów - tabela
	public function renderList(){

		$fields_list = array(
		  'id' => array(
			  'title' => $this->l("ID"),
			  'search' => false,
			  'align' => 'center'
		  ),
		  'urlimage' => array(
			  'title' => $this->l("Image"),
			  'search' => false,
			  'class' => "changetoimage",
			  'align' => 'center'
		  ),
		  'image_name' => array(
			  'title' => $this->l("Name"),
			  'search' => false,
			  'align' => 'center'
			  
		  ),
		  'cat_id' => array(
			'title' => $this->l("Cat ID"),
			'search' => false,
			'align' => 'center'
			
		  ),
		  'height' => array(
			'title' => $this->l("Height"),
			'search' => false,
			'align' => 'center'
			
		  ),
		  'width' => array(
			'title' => $this->l("Width"),
			'search' => false,
			'align' => 'center'
			
		  ),
		  'position' => array(
			'title' => $this->l("Position"),
			'search' => false,
			'align' => 'center'
			
		  ),
		
          
		);

		if (!Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
			unset($fields_list['shop_name']);
		}

		//Konfiguracja LISTY ze zdefiniowanymi w $fields_list kolumnami
		$helper_list = new HelperList();
		
		$helper_list->module = $this;	//requires the instance of the module that will use the form
		$helper_list->title = $this->l('Pictograms');	//Wyświetlana nazwa sekcji z listą
		$helper_list->shopLinkType = '';
		$helper_list->no_link = true;
		$helper_list->show_toolbar = true;	// false -> remove toolbar (toolbar displayed or not)
		$helper_list->simple_header = false;
		$helper_list->identifier = 'id';
		$helper_list->table = '_'.$this->name;
		$helper_list->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;	//It's the url of the current page
		$helper_list->token = Tools::getAdminTokenLite('AdminModules');	//requires a unique token for the module. getAdminTokenLite() helps us generate one.
		$helper_list->actions = array('delete');	//Akcja dla wiersza (dodany button)
		$helper_list->bulk_actions = array('delete'=> array('text'=> $this->l('Delete selected')));	//Akcje dla wielu zaznaczonych elementów


		// This is needed for displayEnableLink to avoid code duplication
		$this->_helperlist = $helper_list;

		/* Retrieve list data */
		$fullList = $this->getSlideList(); //Pobranie listy z bazy danych
		$helper_list->listTotal = count($fullList);	//Liczba wierszy wszystkich slide'ów z bazy

		/* Paginate the result */
		$page = ($page = Tools::getValue('submitFilter' . $helper_list->table)) ? $page : 1;	//wyświetlana strona listy
		$pagination = ($pagination = Tools::getValue($helper_list->table . '_pagination')) ? $pagination : 50;	//Liczba wierszy na stronę
		$partOfTheList = $this->paginateTable($fullList, $page, $pagination);	//Podział pełnej listy na listę dla danej podstrony

		//parent::renderList();
		return $helper_list->generateList($partOfTheList, $fields_list);
	}

	//Podział tabeli - zwraca nową tabele z elementami OD <-> DO. Tworzy podział kolejnych "pagination" elementów na stronę. 
	public function paginateTable($fullList, $page = 1, $pagination = 50)
	{
		if (count($fullList) > $pagination) {
			$fullList = array_slice($fullList, $pagination * ($page - 1), $pagination);//$partOfTheList
		}
		return $fullList;//$partOfTheList
	}

	//zapis zdjęcia na serwerze
	public function postFiles($image_name)
	{
		if(isset($_FILES))
		{
			if ($error = ImageManager::validateUpload($_FILES['perfect_productbanner']))
			{
			  //return $error;
			  return false;
			}
			else 
			{
			  $file_name = $_FILES['perfect_productbanner']['name'];
			  $ext = substr($file_name, strrpos($file_name, '.') + 1);
			  $file_name = $image_name.'_'.idate("U").'.png'; //podana nazwa + aktualna data w postaci int sekund
			  if (!move_uploaded_file($_FILES['perfect_productbanner']['tmp_name'], _PS_MODULE_DIR_.DIRECTORY_SEPARATOR.$this->name.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$file_name))
			  {
				  //return $this->displayError($this->trans('An error occurred while attempting to upload the file.', array(), 'Admin.Notifications.Error'));
				  return false;
			  }
			  else
			  {
				  $myfile = _PS_BASE_URL_.__PS_BASE_URI__.'modules/'.$this->name.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$file_name;
				  return $myfile;
			  }
			}
		}
	}

	//Przycisk Delete na liście slide'ów
    public function displayDeleteLink($token = null, $id, $name = null)
    {
		//Przypisanie akcji do zmiennych
        $this->smarty->assign(array(
            'action' => $this->trans('Delete', array(), 'Admin.Actions'),
            'disable' => !((int) $id > 0),
            'id' => $id
        ));
		//Wyświetlenie html przycisków
        return $this->display(__FILE__, 'views/templates/delete_button_html.tpl');
    }

	//Formularz potwierdzenia usuwania slide'ów - zawiera dane usuwanego elementu
    public function diplayModal()
    {
		$html = ' <!--modal-->
		<div class="modal fade" id="Modal2" tabindex="-1" role="dialog" aria-labelledby="Modal2Label" aria-hidden="true">
		  <div class="modal-dialog" role="document">
			  <div class="modal-content">
					  <div class="modal-header">
						  <h1 class="modal-title" id="Modal2Label">'.$this->l('Are you sure you want to delete?').'</h1>
					  </div>
					  <div class="modal-body">
						  <form action="" method="post" class="defaultForm form-horizontal" enctype="multipart/form-data" novalidate="">
						  <input type="hidden" name="to-remove" value="">
						  <input type="submit" name="submit-remove" class="button btn btn-danger" value="'.$this->l('Remove').'">
						  <button type="button" class="btn btn-default" data-dismiss="modal">'.$this->l('Close').'</button>
						  </form>
					  </div>
		  
			  </div>
		</div>

		</div>

		<!--modal end-->';

		return $html;
    }

	public function getCategory()
	{
		$cats = Category::getCategories( (int)($cookie->id_lang), true, false  ) ;
		foreach($cats as $cat)
		{
			$cat_arr[] = Array(
				'id' => $cat['id_category'],
				'name' => $cat['name']
			);
		}
		return $cat_arr;

	}

//------------------------------------------------------------------
//Obsługa bazy danych
//------------------------------------------------------------------

	//Tabela w bazie danych
	public function createTable(){
		Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS '. _DB_PREFIX_ .'perfect_productbanner(
		  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		  urlimage text,
		  image_name varchar(225),
		  cat_id varchar(3),
		  height varchar(225),
		  width varchar(225),
		  position varchar(5))'
		);
	}
	public function dropTable()
	{
		Db::getInstance()->execute("DROP TABLE ". _DB_PREFIX_ ."perfect_productbanner");
	}
    
	//Lista Slide'ów zapisanych w bazie
	public function getSlideList(){
		$a =  Db::getInstance()->executeS("SELECT `id`,`urlimage`,`image_name`,`cat_id`,`height`,`width`,`position` FROM ". _DB_PREFIX_ ."perfect_productbanner");
		return $a;
	}

	//Zapis w bazie danych slide'ów
	public function insertImageUrl($url, $name, $catsel, $height, $width, $position)
	{
		$url = htmlspecialchars($url);
		$a =  Db::getInstance()->execute("INSERT INTO ". _DB_PREFIX_ ."perfect_productbanner(urlimage,image_name,cat_id,height,width,position) VALUES (\"$url\", \"$name\", \"$catsel\", \"$height\", \"$width\", \"$position\")");
		return $a;
	}
	
	//Usunięcie wiersza Slide'u z bazy danych
    public function removeRow($id)
    {
      Db::getInstance()->execute("DELETE FROM ". _DB_PREFIX_ ."perfect_productbanner WHERE id = $id");
	  Configuration::deleteByName('PERFECT_PRODUCTBANNER_RIGHT');
	  Configuration::deleteByName('PERFECT_PRODUCTBANNER_TOP');
	  Configuration::deleteByName('PERFECT_PRODUCTBANNER_BOT');
    }

//------------------------------------------------------------------
//Wyświetlanie po stronie Klienta
//Czasami po update Presty przestają się wyświetlać. Wtedy trzeba na liście modułów kliknąć Reset
//UWAGA - jeżeli w uninstall() będzie włączone $this->dropTable(), to reset usunie tabelę oraz wszystkie ustawienia, a następnie wgra dane startowe z pustą tabelą
//------------------------------------------------------------------

	//hook do wyświetlenia tpl w wybranym miejscu - tutaj przy produkcie
	public function hookDisplayAfterProductThumbs()
	{
        //Przekazanie parametru tablicy - Lista url
        $tmparr = $this->getSlideList();//Pobranie listy wierszy z bazy danych
        $myURL = array();
        foreach ($tmparr as $value) {
            array_push($myURL,$value);//Lista z jednej kolumny tabeli
        }	 
    
        //Inicjowanie zmiennych do przekazania i wykorzystania w pliku tpl
        global $smarty;
        $this->smarty->assign('lang_iso', Context::getContext()->language->iso_code);	//Przypisanie zmiennej języka
        $this->smarty->assign('mymoduledir', _MODULE_DIR_.$this->name);	//Lokalizacja modułu
        $this->smarty->assign('myURL', $myURL);
        
        //Wyświetl wynik tpl w odpowiednim hook'u po stronie klienta
        return $this->display(__FILE__, 'views/templates/perfect_productbanner_bot.tpl');
	}
	
    public function hookDisplayProductBannerTop()
	{
        //Przekazanie parametru tablicy - Lista url
        $tmparr = $this->getSlideList();//Pobranie listy wierszy z bazy danych
        $myURL = array();
        foreach ($tmparr as $value) {
            array_push($myURL,$value);//Lista z jednej kolumny tabeli
        }
        //Inicjowanie zmiennych do przekazania i wykorzystania w pliku tpl
        global $smarty;
        $this->smarty->assign('lang_iso', Context::getContext()->language->iso_code);	//Przypisanie zmiennej języka
        $this->smarty->assign('mymoduledir', _MODULE_DIR_.$this->name);	//Lokalizacja modułu
        $this->smarty->assign('myURL', $myURL);
        
        //Wyświetl wynik tpl w odpowiednim hook'u po stronie klienta
        return $this->display(__FILE__, 'views/templates/perfect_productbanner_top.tpl');
	}

    public function hookDisplayRightColumnProduct()
	{
        //Przekazanie parametru tablicy - Lista url
        $tmparr = $this->getSlideList();//Pobranie listy wierszy z bazy danych
        $myURL = array();
        foreach ($tmparr as $value) {
            array_push($myURL,$value);//Lista z jednej kolumny tabeli
        }	 
        //Inicjowanie zmiennych do przekazania i wykorzystania w pliku tpl
        global $smarty;
        $this->smarty->assign('lang_iso', Context::getContext()->language->iso_code);	//Przypisanie zmiennej języka
        $this->smarty->assign('mymoduledir', _MODULE_DIR_.$this->name);	//Lokalizacja modułu
        $this->smarty->assign('myURL', $myURL);
        
        //Wyświetl wynik tpl w odpowiednim hook'u po stronie klienta
        return $this->display(__FILE__, 'views/templates/perfect_productbanner_right.tpl');	
    }
}
?>
