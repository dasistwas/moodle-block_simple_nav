<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Used to render the navigation items in the simple_nav block
 */
class block_simple_nav_renderer extends plugin_renderer_base {

    public function simple_nav_tree($items) {
        $depth = 0;
        $type = 0;
        $content = array();
        $doc = new DOMDocument();
        $node = $doc->createElement('ul');
        $mainnode = $doc->appendChild($node);
        $mainnode->setAttribute('class', 'block_tree list');
        $mainnode->setAttribute('role', 'tree');
        $mainnode->setAttribute('data-ajax-loader', 'block_navigation/nav_loader');
        $coursenode = null;

        for ($i = 0; $i < count($items); $i++) {
            $item = $items[$i];
            $externalnode = $this->sn_print_item($item);
            $hidden = $externalnode->expanded == "true" ? "false" : "true";

            if (!is_null($externalnode)) {
                $childnode = $doc->importNode($externalnode, true);

                if ($item['mytype'] == "category" || $item['mytype'] == "home" || $item['mytype'] == "nohome") {
                    $categorynode = $mainnode->appendChild($childnode);
                    $ul = $doc->createElement('ul');
                    $ul->setAttribute('role', "group");
                    $ul->setAttribute('id', $externalnode->itemid);
                    $ul->setAttribute('aria-hidden', $hidden);

                    $categorynode = $categorynode->appendChild($ul);
                } else if ($item['mytype'] == "course") {
                    $coursenode = $categorynode->appendChild($childnode);
                    $ul = $doc->createElement('ul');
                    $ul->setAttribute('role', "group");
                    $ul->setAttribute('id', $externalnode->itemid);
                    $ul->setAttribute('aria-hidden', $hidden);
                    $coursenode = $coursenode->appendChild($ul);
                } else if ($item['mytype'] == "module") {
                    if (!is_null($coursenode)) {
                        $coursenode->appendChild($childnode);
                    } else {
                        $categorynode->appendChild($childnode);
                    }
                }
            }
        }

        // cleanup empty nodes
        $xpath = new DOMXPath($doc);
        foreach ($xpath->query('//ul[not(node())]') as $node) {
            $node->parentNode->removeChild($node);
        }
        $content = $doc->saveHTML();
        return $content;
    }

    /**
     * Render items for display in the simple nav block
     *
     * @param array $item
     * @return DOMDocument
     */
    protected function sn_print_item(array $item) {
        global $CFG, $OUTPUT;
        $doc = new DOMDocument();
        $img = null;
        $baseurl = $CFG->wwwroot;
        $mystartclass = "";
        $itemclass = $item['myclass'];
        $itemtype = $item['mytype'];
        $itemdepth = $item['mydepth'];
        $itemvisibility = $item['myvisibility'];
        $itemicon = $item['myicon'];
        $itemname = $item['myname'];
        $itemid = $item['myid'];
        $isexpandable = (empty($expansionlimit) || ($item->type > navigation_node::TYPE_ACTIVITY || $item->type < $expansionlimit) || ($item->contains_active_node() && $item->children->count() > 0));
        // $isbranch = $isexpandable && ($item->children->count() > 0 || ($item->has_children() && (isloggedin() || $item->type <=
        // navigation_node::TYPE_CATEGORY)));

        if (!empty($this->config->space)) {
            $space_symbol = $this->config->space;
        }

        // if we don't want to show the first node, we use the class "startingpoint" as an indicator
        // to totally skip it
        if (strpos($itemclass, 'startingpoint') !== false) {
            return null;
        }

        // we only want the active branch to be open, all the other ones whould be collapsed
        // $mycollapsed = '';
        // myclass only has a value when it's active
        if (!$itemclass) {
            // $mycollapsed = ' collapsed';
            $expanded = 'false';
        } else {
            // $mycollapsed = '';
            $expanded = 'true';
        }

        // is it a category
        if ($itemtype == 'category') {
            $myurl = $CFG->wwwroot . '/course/index.php?categoryid=' . $itemid;
            $itemclass_li = 'type_category depth_' . $itemdepth . '' . ' contains_branch' . $mystartclass;
            $itemclass_p = 'tree_item branch hasicon ' . $itemclass;
            $itemclass_a = '';
            if ($itemvisibility == 0) {
                $itemclass_a = 'class="dimmed_text"';
            } else {
                $itemclass_a = '';
            }
        } // is it a course
elseif ($itemtype == 'course') {
            // We don't want course-nodes to be open, even when they are active so:
            // $mycollapsed =' collapsed';
            $myurl = $CFG->wwwroot . '/course/view.php?id=' . $itemid;
            $itemclass_li = 'type_course depth_' . $itemdepth . '' /*. $mycollapsed */. ' contains_branch';
            ;
            $itemclass_p = 'tree_item branch hasicon' . $itemclass;
            if ($itemvisibility == 0) {
                $itemclass_a = 'class="dimmed_text"';
            } else {
                $itemclass_a = '';
            }
        } // or the home node
elseif ($itemtype == 'home') {
            $myurl = $CFG->wwwroot;
            $itemclass_li = 'type_unknown depth_1 contains_branch';
            $itemclass_p = 'tree_item branch hasicon ' . $itemclass . ' navigation_node';
            $itemclass_a = '';
        } // or invisible home node
elseif ($itemtype == 'nohome') {
            $myurl = $CFG->wwwroot;
            $itemclass_li = 'type_unknown depth_1 contains_branch simple_invisible';
            $itemclass_p = 'tree_item branch hasicon ' . $itemclass . ' navigation_node';
            $itemclass_a = '';
        } // or a module
elseif ($itemtype == 'module') {
            $myurl = $CFG->wwwroot . '/mod/' . $itemicon . '/view.php?id=' . $itemid;
            $itemclass_li = 'contains_branch item_with_icon';
            $itemclass_p = 'tree_item leaf hasicon' . $itemclass;
            $itemclass_a = '';
            $isexpandable = false;
            if ($itemvisibility == 0) {
                $itemclass_a = 'class="dimmed_text"';
            } else {
                $itemclass_a = '';
            }
            $displayoption = get_config('block_simple_nav', 'displayoptions');
            $img = $doc->createElement('img');
            $img->setAttribute('alt', "");
            if ($displayoption === '1') {
                $img->setAttribute('class', "smallicon navicon");
                $img->setAttribute('src',
                        $baseurl . '/theme/image.php?theme=standard&amp;image=icon&amp;rev=295&amp;component=' . $itemicon);
            } else if ($displayoption === '2') {
                $img->setAttribute('class', "smallicon navicon");
                $img->setAttribute('src',
                        $baseurl . '/theme/image.php?theme=' . $CFG->theme . '&amp;image=t/collapsed&amp;rev=295&amp;component=core');
            } else {
                $img->setAttribute('class', "smallicon navicon navigationitem");
                $img->setAttribute('src',
                        $baseurl . '/theme/image.php?theme=' . $CFG->theme . '&amp;image=i/navigationitem&amp;rev=295&amp;component=core');
            }
        }
        $li = $doc->createElement('li');
        $li->itemid = $itemid . html_writer::random_id() . "_group";
        $li->expanded = $expanded;
        $li->setAttribute('class', $itemclass_li);
        $li = $doc->appendChild($li);
        $p = $doc->createElement('p');
        $p = $li->appendChild($p);
        $p->setAttribute('class', $itemclass_p);
        if (isset($isexpandable) && $isexpandable) {
            $p->setAttribute('aria-expanded', $expanded);
        }
        $p->setAttribute('role', "treeitem");
        $p->setAttribute('aria-owns', $li->itemid);
        $text = $doc->createTextNode($itemname);
        $a = $doc->createElement('a');
        $a->appendChild($text);
        $a = $p->appendChild($a);
        $a->setAttribute('class', $itemclass_a);
        $a->setAttribute('href', $myurl);
        if (!is_null($img)) {
            $a->appendChild($img);
        }
        return $li;
    }
}
