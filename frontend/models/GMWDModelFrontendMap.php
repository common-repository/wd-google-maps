<?php
        
class GMWDModelFrontendMap extends GMWDModelFrontend{
	////////////////////////////////////////////////////////////////////////////////////////
	// Events                                                                             //
	////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////
	// Constants                                                                          //
	////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////
	// Variables                                                                          //
	////////////////////////////////////////////////////////////////////////////////////////

	////////////////////////////////////////////////////////////////////////////////////////
	// Constructor & Destructor                                                           //
	////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////
	// Public Methods                                                                     //
	////////////////////////////////////////////////////////////////////////////////////////
	public function get_map(){

		global $wpdb;
		$params = $this->params;
    
		$id = isset($params["map"]) ? (int) $params["map"] : 0;
		$shortcode_id = isset($params["id"]) ? (int) $params["id"] : '';
        if(!$shortcode_id){
            echo "<h2>". __("Invalid Request","gmwd"). "</h2>";
        } 
        elseif(!$id){
            echo "<h2>". __("Please Select Map","gmwd"). "</h2>";
        }
        else{ 
            $row = parent::get_row_by_id($id, "maps");   
         
            if($row && $row->published == 1) {
                $row->height = $row->height ? $row->height : 300;
                // params for widget
                $row->width = isset($params["width"])  ? esc_html(stripslashes($params["width"])) : $row->width;
                $row->height = isset($params["height"]) ? esc_html(stripslashes($params["height"])) : $row->height;
                $row->width_percent = isset($params["width_unit"]) ? esc_html(stripslashes($params["width_unit"])) : $row->width_percent;                
                $row->zoom_level = isset($params["zoom_level"]) && $params["zoom_level"] ? esc_html(stripslashes($params["zoom_level"])) : $row->zoom_level;
                $row->type = isset($params["type"]) &&  $params["type"] ? esc_html(stripslashes($params["type"])) : $row->type;
               
                return $row;
            }
            else{
               echo "<h2>". __("Invalid Request","gmwd"). "</h2>";
            }
        }
	
	}
	
	public function get_overlays($id){
		global $wpdb;
		$params = $this->params;
		$id = (int)$params["map"];
		$overlays = new StdClass();
        $overlays->markers = array();
        $overlays->polygons = array();
        $overlays->polylines = array();
		if($id){
			
           
      $radius = isset($_POST["radius"]) ? (float) (stripslashes($_POST["radius"])) : 0;
      $lat = isset($_POST["lat"]) ? (float) (stripslashes($_POST["lat"])) : 0;
      $lng = isset($_POST["lng"]) ? (float) (stripslashes($_POST["lng"])) : 0;
            $distance_in = isset($_POST["distance_in"]) ? sanitize_text_field(stripslashes($_POST["distance_in"])) : "";
            $distance_in = $distance_in == "km" ? 6371 : 3959;
            
            $select_distance = "";
            $having_distance = "";
            if($distance_in && $radius && $lat && $lng){
                $select_distance = ", ( ".$distance_in." * acos( cos( radians(".$lat.") ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(".$lng.") ) + sin( radians(".$lat.") ) * sin( radians( lat ) ) ) ) AS distance";
                $having_distance = "HAVING distance<".$radius;
            }
            
      $limit = isset($_POST["limit"]) ? (int) (stripslashes($_POST["limit"])) : 20;
      $limit_by = " LIMIT 0, " . $limit;
      
            $markers = $wpdb->get_results("SELECT T_MARKERS.* ".$select_distance." FROM  " . $wpdb->prefix . "gmwd_markers AS T_MARKERS  WHERE T_MARKERS.published = '1' AND T_MARKERS.map_id= '".$id."' ".$having_distance." ORDER BY T_MARKERS.id");	

			$row_markers = array();
			foreach($markers as $marker){
                $marker->description = '';
				$row_markers[$marker->id] = $marker;			
			}
			$overlays->markers  = $row_markers;
            $overlays->all_markers  = $row_markers;    
		
			$polygons = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "gmwd_polygons WHERE map_id= '".$id."' AND published = '1'  ORDER BY id ");
			$row_polygons = array();
			foreach($polygons as $polygon){
				$row_polygons[$polygon->id] = $polygon;			
			}
			$overlays->polygons = $row_polygons;
			
			$polylines = $wpdb-> get_results("SELECT * FROM " . $wpdb->prefix . "gmwd_polylines WHERE map_id= '".$id."' AND published = '1' ORDER BY id ");
			$row_polylines = array();
			foreach($polylines as $polyline){
				$row_polylines[$polyline->id] = $polyline;			
			}
			$overlays->polylines = $row_polylines;

		}
        return $overlays;
	}
    

	public function get_theme($theme_id){
		global $wpdb;
		$theme = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "gmwd_themes WHERE `default`='1'");	          
		return $theme;		
	}

    public function get_options(){
        global $wpdb;

        $query = "SELECT * FROM ". $wpdb->prefix . "gmwd_options ";
        $rows = $wpdb->get_results($query);

        $options = new stdClass();
        foreach ($rows as $row) {
            $name = $row->name;
            $value = $row->value !== "" ? $row->value : $row->default_value;
            $options->$name = $value;
        }

        return $options;

    }
	
	////////////////////////////////////////////////////////////////////////////////////////
	// Getters & Setters                                                                  //
	////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////
	// Private Methods                                                                    //
	////////////////////////////////////////////////////////////////////////////////////////

	////////////////////////////////////////////////////////////////////////////////////////
	// Listeners                                                                          //
	////////////////////////////////////////////////////////////////////////////////////////
}