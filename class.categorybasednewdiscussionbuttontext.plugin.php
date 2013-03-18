<?php if (!defined('APPLICATION')) exit();

$PluginInfo['CategoryBasedNewDiscussionButtonText'] = array(
	'Name' => 'Category based "New Discussion" button text',
	'Description' => 'Customize text of the "New Discussion" button - for each category.',
	'Version' => '0.1',
	'RequiredApplications' => array('Vanilla' => '2.1a1'),
	'RequiredTheme' => FALSE,
	'RequiredPlugins' => FALSE,
	'SettingsUrl' => 'settings/categorybasednewbuttontext',
	'SettingsPermission' => 'Garden.Settings.Manage',
	'Author' => "Alessandro Miliucci",
	'AuthorEmail' => 'lifeisfoo@gmail.com',
	'AuthorUrl' => 'http://forkwait.net',
	'License' => 'GPL v3'
);

class CategoryBasedNewButtonTextPlugin implements Gdn_IPlugin {
  
  public function SettingsController_CategoryBasedNewButtonText_Create($Sender) {
    $Sender->Permission('Garden.Plugins.Manage');
    $Sender->AddSideMenu();
    $Sender->Title('Category Based New Button Text');
    $ConfigurationModule = new ConfigurationModule($Sender);
    $ConfigurationModule->RenderAll = True;
    $DynamicSchema = array();
    foreach(CategoryModel::Categories() as $Cat){
      $CatName = $Cat["Name"];
      $CatNameNoSpace = self::normalizeName($CatName);
      $SingleCatSchema = 
	array('Plugins.CategoryBasedNewButtonText.'.$CatNameNoSpace => 
	      array('LabelCode' => $CatName, 
		    'Control' => 'TextBox', 
		    'Default' => C('Plugins.CategoryBasedNewButtonText.'.$CatNameNoSpace, '')
		    )
	      );
      $DynamicSchema = array_merge($DynamicSchema, $SingleCatSchema);
    }
    
    $Schema = $DynamicSchema;
    $ConfigurationModule->Schema($Schema);
    $ConfigurationModule->Initialize();
    $Sender->View = dirname(__FILE__) . DS . 'views' . DS . 'cbnbsettings.php';
    $Sender->ConfigurationModule = $ConfigurationModule;
    $Sender->Render();
  }
  
  //USELESS because in discussions controller there are may categories
  public function DiscussionsController_BeforeNewDiscussionButton_Handler($Sender) {
    //probably in future...
  }
  
  public function CategoriesController_BeforeNewDiscussionButton_Handler($Sender) {
    if($Sender->Category) {
      self::setButtonText($Sender->Category);//Category is an object
    }
  }
  
  public function DiscussionController_BeforeNewDiscussionButton_Handler($Sender) {
    if($Sender->CategoryID) {
      $Cats = CategoryModel::Categories();
      foreach($Cats as $Cat){
	if($Cat["CategoryID"] == $Sender->CategoryID){
	  self::setButtonText($Cat);//Category is an array
	  break;
	}
      }
    }
  }

  public function PostController_BeforeNewDiscussionButton_Handler($Sender) {
    $Data = GetValue("Data", $Sender);
    foreach($Data["Breadcrumbs"] as $Bc){
      if($Bc["CategoryID"] && $Bc["Name"]){
	self::setButtonText($Bc);//array case
	break;
      }
    }
  }
  
  private static function setButtonText($Cat){//Category object
    $CatName = $Cat->Name;
    if(!$CatName){ $CatName = $Cat["Name"];}//if $Cat is an array
    $CustomText = C('Plugins.CategoryBasedNewButtonText.'.$CatName, '');
    if(strcmp($CustomText,'') != 0) {
      Gdn::Locale()->SetTranslation(
				    'Start a New Discussion', 
				    C('Plugins.CategoryBasedNewButtonText.'.self::normalizeName($CatName), '')
				    );
    }
  }
  
  private static function normalizeName($CatName){
    return str_replace(" ", "_", $CatName);
  }
  
  public function Setup() {}
}