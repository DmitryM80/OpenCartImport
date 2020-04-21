<?php



class ControllerExtensionModuleCsvImport extends Controller {

	private $error = array();

	public $codename = 'csv_import';

    private $fields;
    private $importer;

//    private $table;
    private $result; 
    private $import_fields;

    public function index() {

		$this->load->language('extension/module/csv_import');
		$this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $this->load->model('catalog/csv_import');
        $this->load->model('catalog/tango_import');
        $this->load->model('catalog/cleo_import');
        $this->load->model('catalog/texdesign_import');
        $this->load->model('catalog/texdesignpc_import');
        $this->load->model('catalog/texdesignbt_import');
        $this->load->model('catalog/texdesignpd_import');
        $this->load->model('catalog/texdesigndc_import');
        $this->load->model('catalog/texdesignbs_import');
        $this->load->model('catalog/texdesignst_import');
        $this->load->model('catalog/kingsilkbc_import');
        $this->load->model('catalog/kingsilkpw_import');
        $this->load->model('catalog/kingsilkbt_import');
        $this->load->model('catalog/kingsilkbs_import');
        $this->load->model('catalog/kingsilk_import');

        $data['fields'] = [];
        $data['result'] = [];
        $data['import_fields'] = '';
        $br = '';


        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $products_added = 0;
            $products_updated = 0;
            require (__DIR__ . '/CsvImporter.php');

            if ($this->request->files) {
                $uploaded_file = $this->uploadFile();

                if($uploaded_file) {

                    $this->importer = new CsvImporter($uploaded_file, true, ',', 2000);

                    // getCsv(int) limit amount of products to int on develop
                    $this->result = $this->importer->getCsv();
                    $data['result'] = $this->result;
                    
                    $this->fields = $this->importer->getHead();
                    $data['fields'] = $this->fields;


                    $this->import_fields = $this->importer->getHead();
                    $data['import_fields'] = $this->import_fields;

                    if ($_POST['supplier'] === 'valtery') {
                        $products_added_updated = $this->model_catalog_csv_import->store_db($this->import_fields,
                            $this->result);
                    }
                    elseif ($_POST['supplier'] === 'tango') {
                        $products_added_updated = $this->model_catalog_tango_import->store_db($this->import_fields,
                            $this->result);
                    }
                    elseif ($_POST['supplier'] === 'cleo') {
                        $products_added_updated = $this->model_catalog_cleo_import->store_db($this->import_fields,
                            $this->result);
                    }
                    elseif ($_POST['supplier'] === 'tex-design') {
                        $products_added_updated = $this->model_catalog_texdesign_import->store_db($this->result);
                    }
                    elseif ($_POST['supplier'] === 'tex-design_pillow_cases') {
                        $products_added_updated = $this->model_catalog_texdesignpc_import->store_db($this->result);
                    }
                    elseif ($_POST['supplier'] === 'tex-design_blankets') {
                        $products_added_updated = $this->model_catalog_texdesignbt_import->store_db($this->result);
                    }
                    elseif ($_POST['supplier'] === 'tex-design_plaids') {
                        $products_added_updated = $this->model_catalog_texdesignpd_import->store_db($this->result);
                    }
                    elseif ($_POST['supplier'] === 'tex-design_duvet_cover') {
                        $products_added_updated = $this->model_catalog_texdesigndc_import->store_db($this->result);
                    }
                    elseif ($_POST['supplier'] === 'tex-design_bedspreads') {
                        $products_added_updated = $this->model_catalog_texdesignbs_import->store_db($this->result);
                    }
                    elseif ($_POST['supplier'] === 'tex-design_sheets') {
                        $products_added_updated = $this->model_catalog_texdesignst_import->store_db($this->result);
                    }
                    elseif ($_POST['supplier'] === 'kingsilk') {
                        $products_added_updated = $this->model_catalog_kingsilk_import->store_db($this->result);
                    }
                    elseif ($_POST['supplier'] === 'kingsilk_bedclothes') {
                        $products_added_updated = $this->model_catalog_kingsilkbc_import->store_db($this->result);
                    }
                    elseif ($_POST['supplier'] === 'kingsilk_pillows') {
                        $products_added_updated = $this->model_catalog_kingsilkpw_import->store_db($this->result);
                    }
                    elseif ($_POST['supplier'] === 'kingsilk_blankets') {
                        $products_added_updated = $this->model_catalog_kingsilkbt_import->store_db($this->result);
                    }
                    elseif ($_POST['supplier'] === 'kingsilk_bedspreads') {
                        $products_added_updated = $this->model_catalog_kingsilkbs_import->store_db($this->result);
                    }
                    elseif ($_POST['supplier'] === 'mioletto') {
                        $this->load->model('catalog/mioletto_import');
                        $products_added_updated = $this->model_catalog_mioletto_import->store_db($this->result);
                    }

                    $csv_import_result = explode('_', $products_added_updated);

                    $products_added = $csv_import_result[0];
                    $products_updated = $csv_import_result[1];
                    if($products_updated != 0) $br = '<br>';
                    $this->session->data['success'] = '';
                    $this->session->data['error_warning'] = '';
                }



                if($products_added != 0) {
                    $this->session->data['success'] = $this->language->get('text_cats_added') . $products_added . $br;
                } else {
                    $this->session->data['error_warning'] = $this->language->get('text_cats_!_added');
                }

                if($products_updated != 0) {
                    $this->session->data['success'] .= $this->language->get('text_cats_updated') . $products_updated;
                } /*else {
                    $this->session->data['error_warning'] = $this->language->get('text_cats_!_added');
                }*/

                 


                 // $finished = $this->model_catalog_csv_import->store($this->table, $this->fields, $this->result);

                /*if ($finished) {
                    $this->session->data['success'] = $this->language->get('text_success');
                    // $data['success'] = $this->language->get('text_success');
                } else {
                    $this->session->data['error_warning'] = $this->language->get('text_empty');
                    // $data['error_warning'] = $this->language->get('text_empty');
                }*/

                //$this->response->redirect($this->url->link('extension/module/csv_import', 'token=' . $this->session->data['token'], true));

            } 
        }

       

        // $tables = $this->model_catalog_csv_import->getTables();


        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['entry_status'] = $this->language->get('entry_status');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');


        // $data['tables'] = $tables;

        $data['action'] = $this->url->link('extension/module/csv_import', 'token=' . $this->session->data['token'], true);

        $data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=module', true);


        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->session->data['error_warning'])) {
            $data['error_warning'] = $this->session->data['error_warning'];
            unset($this->session->data['error_warning']);
        } else {
            $data['entry_warning'] = '';
        }




		$this->response->setOutput($this->load->view('extension/module/csv_import', $data));
	}

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/csv_import')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }


    public function debug($var = null)
    {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
    }


    public function uploadFile ()
    {
        $target = null;

        if (!file_exists(DIR_CACHE . $this->codename . '/')) {
            mkdir(DIR_CACHE . $this->codename . '/', 0777);
        }

        $filename = $this->request->files['upload_file']['name'];
        if($filename) {
            $info = pathinfo($filename);
            $ext = $info['extension'];
            $target = DIR_CACHE . $this->codename . '/import' .'.'. $ext;

            move_uploaded_file($_FILES['upload_file']['tmp_name'], $target);
        }

        return $target;
    }



}