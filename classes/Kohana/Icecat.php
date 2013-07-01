<?php

defined('SYSPATH') OR die('No direct script access.');

class Kohana_Icecat {

    protected static $_instance;
    protected $_config;
    public $_EAN;
    public $_product = array();

    /**
     * Singleton pattern
     *
     * @return Icecat
     */
    public static function instance() {
        if (!isset(Icecat::$_instance)) {
            // Load the configuration for this type
            $config = Kohana::$config->load('icecat');
            // Create a new session instance
            Icecat::$_instance = new Icecat($config);
        }

        return Icecat::$_instance;
    }

    /**
     * Loads configuration options.
     *
     * @param   array  $config  Config Options
     * @return  void
     */
    public function __construct($config = array()) {
        // Save the config in the object
        $this->_config = $config;
        if (empty($this->_config['username'])) {
            echo $this->_gm('The user name is empty!');
            return FALSE;
        }
    }

    private static function _gm($message) {
        return 'Icecat: ' . __($message) . "<br />\n";
    }

    public function getProductSpec($ean, $drawdescription = 0, $drawpicture = 0) {

        // Return 0 and exit function if no EAN available
        if ($ean == null) {
            return 0;
        }

        // Get the product specifications in XML format
        $context = stream_context_create(array(
            'http' => array(
                'header' => "Authorization: Basic " . base64_encode($this->_config['username'] . ":" . $this->_config['password'])
            )
        ));

        try {
            $data = file_get_contents('http://data.icecat.biz/xml_s3/xml_server3.cgi?ean_upc=' . $ean . ';lang=' . $this->_config['lang'] . ';output=productxml', false, $context);
        } catch (Exception $exc) {
            echo $this->_gm($exc->getMessage());
            return FALSE;
        }

        $xml = new SimpleXMLElement($data);

        // Create arrays of item elements from the XML feed
        $this->_product['Picture'] = $xml->xpath("//Product");
        $this->_product['Description'] = $xml->xpath("//ProductDescription");
        $this->_product['categories'] = $xml->xpath("//CategoryFeatureGroup");
        $this->_product['spec'] = $xml->xpath("//ProductFeature");

        //Draw product specifications table if any specs available for the product
        if ($this->_product['spec'] != null) {
            $categoryList = array();
            foreach ($this->_product['categories'] as $categoryitem) {
                $catId = intval($categoryitem->attributes());
                $titleXML = new SimpleXMLElement($categoryitem->asXML());
                $title = $titleXML->xpath("//Name");
                $catName = $title[0]->attributes();
                //echo $catId . $catName['Value']. "<br />";
                $categoryList[$catId] = $catName['Value'];
            }

            $specs = "<table class='productspecs'>";
            $i = 0;

            $drawnCategories = array();

            foreach ($this->_product['spec'] as $item) {
                $specValue = $item->attributes();
                $titleXML = new SimpleXMLElement($item->asXML());
                $title = $titleXML->xpath("//Name");
                $specName = $title[0]->attributes();
                $specCategoryId = intval($specValue['CategoryFeatureGroup_ID']);

                if ($specName['Value'] != "Source data-sheet") {
                    $class = $i % 2 == 0 ? "odd" : "even";
                    $specs .= "<tr class='" . $class . "'><td><table>";
                    if (!in_array($specCategoryId, $drawnCategories)) {
                        $specs .= "<tr class='speccategory'><th><h3>" . $categoryList[$specCategoryId] . "</h3></th></tr>";
                        $drawnCategories[$i] = $specCategoryId;
                    }
                    $spec_txt = ($this->_config['utf8_decode'] === FALSE)?$specName['Value']:utf8_decode($specName['Value']);
                    $specs .= "<tr><th>" .  $spec_txt . ":</th></tr>
                            <tr><td>";
                    if ($specValue['Presentation_Value'] == "Y") {
                        $specs .= "<img src='" . MODPATH . "/icecat/images/check_green.png' alt='" . __('Yes') . "' />" . __('Yes');
                    } else if ($specValue['Presentation_Value'] == "N") {
                        $specs .= "<img src='" . MODPATH . "/icecat/images/check_red.png' alt='{" . __('no') . "' />" . __('no');
                    } else {
                        $spec_txt2 = ($this->_config['utf8_decode'] === FALSE)? $specValue['Presentation_Value']:utf8_decode($specValue['Presentation_Value']);
                        $specs .= str_replace('\n', '<br />', $spec_txt2);
                    }
                    $specs .= "</td></tr></table></td></tr>";
                }
                $i++;
            }
            $specs .= "</table>";

            //Draw product description and link to manufacturer if available
            if ($drawdescription != 0) {
                foreach ($this->_product['Description'] as $item) {
                    $productValues = $item->attributes();
                    if ($productValues['URL'] != null) {
                        $specs .= "<p id='manufacturerlink'><a href='" . $productValues['URL'] . "'>Productinformation from manufacturer</a></p>";
                    }
                    if ($productValues['LongDesc'] != null) {
                        $description = utf8_decode(str_replace('\n', '', $productValues['LongDesc']));
                        $description = str_replace('<b>', '<strong>', $description);
                        $description = str_replace('<B>', '<strong>', $description);
                        $description = str_replace('</b>', '</strong>', $description);
                        $specs .= "<p id='manudescription'>" . $description . "</p>";
                    }
                }
            }

            //Draw product picture if available
            if ($drawdescription != 0) {
                foreach ($this->_product['Picture'] as $item) {
                    $productValues = $item->attributes();
                    if ($productValues['HighPic'] != null) {
                        $specs .= "<div id='manuprodpic'><img src='" . $productValues['HighPic'] . "' alt='' /></div>";
                    }
                }
            }
            return $specs;
        } else {
            return 0;
        }
    }

}

