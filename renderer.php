<?php
/**
 * Used to render the navigation items in the simple_nav block
 *
 */
class block_simple_nav_renderer extends plugin_renderer_base {

    public function simple_nav_tree($items) {
        $depth = 0;
        $type = 0;
        foreach ($items as $item) {
        	
        	//category and courses open <ul> tags. if they are empty, we have to make sure they're closed again.

			// We have to check if the category before was empty
        	if ($depth>=$item['mydepth'] && $type == 'category' && $item['mytype'] == 'category') {
        		$content[] = '</li></ul>';
        	}
			
        	//this is to know if we have to close the tags for the branch. We calculate the difference between the old and new branch and add the close tags accordingly
        	if ($depth>$item['mydepth']) {
        		$mydifference = $depth-$item['mydepth'];
        		$content[] = str_repeat('</li></ul>',$mydifference);
        	}
        	// Every time we the last item was a module and the new isn't, we can close <ul> as well as <li>
        	elseif ($item['mytype'] <> 'module' && $type == 'module') {
        		$content[] = '</li></ul>';
        	}
        	// if depth stays the same, we can just close the <li> tag
        	elseif ($depth==$item['mydepth']) {
        		$content[] = '</li>';
        	}
        	
        	//if the old item was course and the new is module, we have to open ul
        	if ($item['mytype'] == 'module' && ($type == 'course' || $type == 'invisiblecourse')) {
        		$content[] = '<ul>';
        	}
        	//print out html code for the item
        	$content[] = $this->sn_print_item($item['myclass'], $item['myid'], $item['myname'], $item['mydepth'], $item['mytype'], $item['mypath'], $item['myicon'], $item['myvisibility']);
        	// keep the information for the next loop
        	$depth = $item['mydepth'];
        	$myid = $item['myid'];
        	$type = $item['mytype'];
        }
		$content[] = '</li></ul>';
		$content = implode($content);
        return $content;
    }
    
    protected function sn_print_item($myclass, $myid, $myname, $mydepth, $mytype, $mypath, $myicon, $myvisibility) {
		global $CFG, $OUTPUT;

		$icon = '';
		$baseurl =$CFG->wwwroot;

		if (! empty($this->config->space)) {
    		$space_symbol = $this->config->space;
		}
		// we only want the active branch to be open, all the other ones whould be collapsed
		$mycollapsed ='';
		// myclass only has a value when it's active
		if (!$myclass) {
			$mycollapsed =' collapsed';
		}
		else {
			$mycollapsed ='';
		}
		// is it a category
		if ($mytype == 'category') {
			$myurl =$CFG->wwwroot.'/course/category.php?id='.$myid;

			$myclass_ul_open = '';
			$myclass_li = 'type_category depth_'.$mydepth.''.$mycollapsed.' contains_branch';
			$myclass_p = 'tree_item branch'.$myclass;
			$myopentag = '<ul>';
			$myclass_a = '';
			if ($myvisibility == 0) {
				$myclass_a = 'class="dimmed_text"';
			}
			else {
				$myclass_a = '';
			}

		}
		// is it a course
		elseif ($mytype == 'course') {

			$myurl =$CFG->wwwroot.'/course/view.php?id='.$myid;

			$myclass_ul_open = '';
			$myclass_li = 'type_course depth_'.$mydepth.''.$mycollapsed.' contains_branch';;
			$myclass_p = 'tree_item branch hasicon'.$myclass;
			$myopentag = '';
			
			if ($myvisibility == 0) {
				$myclass_a = 'class="dimmed_text"';
			}
			else {
				$myclass_a = '';
			}
			

		}
		// or the home node
		elseif ($mytype == 'home') {
			$myurl =$CFG->wwwroot;

			$myclass_ul_open = '<ul class="block_tree list">';
			$myclass_li = 'type_unknown depth_1 contains_branch';
			$myclass_p = 'tree_item branch '.$myclass.' navigation_node';
			$myopentag = '<ul>';
			$myclass_a = '';

		}
		// or a module
		elseif ($mytype == 'module') {
			$myurl =$CFG->wwwroot.'/mod/'.$myicon.'/view.php?id='.$myid;
			$myclass_ul_open = '';
			$myclass_li = 'contains_branch item_with_icon';
			$myclass_p = 'tree_item leaf hasicon'.$myclass;
			$myopentag = '';
			$myclass_a = '';
			
			if ($myvisibility == 0) {
				$myclass_a = 'class="dimmed_text"';
			}
			else {
				$myclass_a = '';
			}

			$icon = '<img alt="'.$myicon.'" class="smallicon navicon" title="'.$myicon.'" src="'.$baseurl.'/theme/image.php?theme=standard&amp;image=icon&amp;rev=295&amp;component='.$myicon.'">';
		}
		
		$myitem = $myclass_ul_open.'<li class="'.$myclass_li.'"><p class="'.$myclass_p.'"><a '.$myclass_a.' href="'.$myurl.'">'.$icon.''.$myname.'</a></p>'.$myopentag;
		return $myitem;
	}
}
