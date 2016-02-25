<?php
require_once 'scripts/classes/Class_ScriptBase.php';

class SiteMap extends ScriptBase{

    const MAX_FILESIZE = 7340032;     //7 Mbyte
    const PRODUCT_PROCESS_NUM = 500;
    const FILE_EXTENSION = ".xml";

    private $_sitemapFile;

    /*
      INIT: Create sitemap folder and delete all files in it

      @params $file Sitemap filename
    */
    public function __construct($file = 'sitemap')
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        parent::__construct();
        $this->_sitemapFile = $this->_baseDir . 'sitemap/' . $file;

        if(!is_dir($this->_baseDir . "sitemap"))
        {
          mkdir($this->_baseDir . "sitemap");
        }
        $files = glob($this->_baseDir . "sitemap" . DIRECTORY_SEPARATOR . '*');
        if($files)
        {
          foreach($files as $file)
          {
            if(is_file($file))
            {
              unlink($file);
            }
          }
        }
    }

    /*
      Generate sitemap files and gzip all and create an index file at the end
    */
    public function generate()
    {
      //HEADER
      $output  = '<?xml version="1.0" encoding="UTF-8"?>';
      $output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
      file_put_contents($this->_sitemapFile . self::FILE_EXTENSION, $output);

      //FIRST CREATE INFO PAGES
      $this->writeInfoPages();

      //NEXT CREATE MANUFACTURERS
      $this->writeManufacturers();

      //NEXT CREATE CATEGORY
      $this->writeCategory();

      //NEXT CREATE PRODUCTS
      $this->writeProducts();

      //END
      $output  = '</urlset>';
      file_put_contents($this->_sitemapFile.self::FILE_EXTENSION, $output, FILE_APPEND);

      //CREATE SITEMAP INDEX
      $this->createIndexFile();
    }

    /*
      Compress files and create one index file
    */
    protected function createIndexFile()
    {
      $ouput = '<?xml version="1.0" encoding="UTF-8"?>';
      $output .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

      $files = glob($this->_baseDir . "sitemap" . DIRECTORY_SEPARATOR . '*');
      if($files)
      {
        foreach($files as $file)
        {
          $this->gzCompressFile($file);
          $output .= '<sitemap>';
          $output .= '<loc>' . HTTP_SERVER . 'sitemap/' . basename($file) . '.gz</loc>';
          $output .= '<lastmod>' . date("Y-m-d") . '</lastmod>';
          $output .= '</sitemap>';
        }
      }
      $output .= '</sitemapindex>';

      file_put_contents($this->_baseDir . 'sitemapindex.xml', $output);
    }

    protected function gzCompressFile($source, $level = 9)
    {
      $dest = $source . '.gz';
      $mode = 'wb' . $level;
      $error = false;
      if ($fp_out = gzopen($dest, $mode))
      {
        if ($fp_in = fopen($source,'rb'))
        {
          while (!feof($fp_in))
          {
            gzwrite($fp_out, fread($fp_in, 1024 * 512));
          }
          fclose($fp_in);
        }
        else
        {
          $error = true;
        }
        gzclose($fp_out);
      }
      else
      {
        $error = true;
      }

      if ($error)
      {
        return false;
      }
      else
      {
        return $dest;
      }
    }

    protected function writeInfoPages()
    {
      $this->load->model('catalog/information');

      $informations = $this->model_catalog_information->getInformations();

      $output = '';

      foreach ($informations as $information) {
        $output .= '<url>';
        $output .= '<loc>' . $this->url->link('information/information', 'information_id=' . $information['information_id']) . '</loc>';
        $output .= '<changefreq>weekly</changefreq>';
        $output .= '<priority>0.5</priority>';
        $output .= '</url>';
      }

      file_put_contents($this->_sitemapFile.self::FILE_EXTENSION, $output, FILE_APPEND);
    }

    protected function writeManufacturers()
    {
      $this->load->model('catalog/manufacturer');

      $manufacturers = $this->model_catalog_manufacturer->getManufacturers();

      $output = '';

      foreach ($manufacturers as $manufacturer) {
        $output .= '<url>';
        $output .= '<loc>' . $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer['manufacturer_id']) . '</loc>';
        $output .= '<changefreq>weekly</changefreq>';
        $output .= '<priority>0.7</priority>';
        $output .= '</url>';
      }

      file_put_contents($this->_sitemapFile.self::FILE_EXTENSION, $output, FILE_APPEND);
    }

    protected function writeCategory()
    {
      $output = '';

      $this->load->model('catalog/category');

      $output .= $this->getCategories(0);

      file_put_contents($this->_sitemapFile.self::FILE_EXTENSION, $output, FILE_APPEND);
    }

    protected function writeProducts()
    {
      $fileCounter = 0;
      $this->load->model('catalog/product');

      $totalProductNum = $this->model_catalog_product->getTotalProducts();
      $to = round($totalProductNum / self::PRODUCT_PROCESS_NUM);
      for($i=0;$i <= $to; $i++)
      {
        $from = $i * self::PRODUCT_PROCESS_NUM;
        $data = array(
          "start" => $from + 1,
          "limit" => $from + self::PRODUCT_PROCESS_NUM
        );
        $products = $this->model_catalog_product->getProducts($data);
        $output = '';
        foreach ($products as $product)
        {
					$output .= '<url>';
					$output .= '<loc>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</loc>';
					$output .= '<changefreq>weekly</changefreq>';
					$output .= '<priority>1.0</priority>';
					$output .= '</url>';
				}
        file_put_contents($this->_sitemapFile.self::FILE_EXTENSION, $output, FILE_APPEND);
        clearstatcache();
        if(filesize($this->_sitemapFile.self::FILE_EXTENSION)>self::MAX_FILESIZE)
        {
          $output  = '</urlset>';
          file_put_contents($this->_sitemapFile.self::FILE_EXTENSION, $output, FILE_APPEND);
          $fileCounter++;
          $this->_sitemapFile = chop($this->_sitemapFile, $fileCounter-1) . $fileCounter;
          $output  = '<?xml version="1.0" encoding="UTF-8"?>';
          $output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
          file_put_contents($this->_sitemapFile.self::FILE_EXTENSION, $output);
        }
      }
    }

    protected function getCategories($parent_id, $current_path = '') {
  		$output = '';

  		$results = $this->model_catalog_category->getCategories($parent_id);

  		foreach ($results as $result) {
  			if (!$current_path) {
  				$new_path = $result['category_id'];
  			} else {
  				$new_path = $current_path . '_' . $result['category_id'];
  			}

  			$output .= '<url>';
  			$output .= '<loc>' . $this->url->link('product/category', 'path=' . $new_path) . '</loc>';
  			$output .= '<changefreq>weekly</changefreq>';
  			$output .= '<priority>0.7</priority>';
  			$output .= '</url>';

  			$output .= $this->getCategories($result['category_id'], $new_path);
  		}

      return $output;
  	}
}

$sm = new SiteMap();
$sm->generate();
?>
