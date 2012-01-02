<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-2009 AlienVault
*   All rights reserved.
*
*   This package is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; version 2 dated June, 1991.
*   You may not use, modify or distribute this program under any other version
*   of the GNU General Public License.
*
*   This package is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this package; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
*   MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
****************************************************************************/

/*
DATAS
file_pcap --> string
xml_psml  --> obj simplexml psml
xml_pdml  --> obj simplexml pdml
text_bin  --> string multiline
METHODS
Tshark($file)       --> creator
get_psml()          --> get data xml_psml
get_pdml()          --> get data xml_pdml
get_text_bin()      --> get data text_bin
print_psml_head()   --> print data head of psml in table 
print_psml_body()   --> print data body of psml in table 
print_pdml($id)     --> print data packet of pdml in tree with jquery
print_text_bin($id) --> print data packet in binary and text
*/

class Tshark {
    //datas
    var $file_pcap;
    var $filter;
    var $xml_psml ;
    var $xml_pdml ;
    var $text_bin ;
    var $max_len ;
    var $description;
    var $error = "";
    var $critical_error = FALSE;
    
    //methods
    //creator
    function Tshark($file, $filter = '') {
        $this->put_file_pcap($file);
        $this->put_filter($filter);
        $cmd    = "capinfos ".$file." 2>&1 1> /dev/null";
        $output = shell_exec($cmd);
        
        if (preg_match("/The file isn't a capture file in a known format/",$output)){
            $this->put_error(_("The file isn't a capture file in a known format")." (".$file.")");
            $this->critical_error = TRUE;
        }elseif(preg_match("/capinfos: Can't open .*: Permission denied/",$output,$match)){
            $this->put_error($match[0]);
            $this->critical_error = TRUE;
        }else{
            if(preg_match("/An error occurred after reading ([0-9]*) packets/",$output,$match)){
                //$this->put_error($match[0].". Only show the ".$match[1]." firsts packets");
                $packeterror = $match[1];
                $cmd         = "editcap -r ".$file." ".$file."_2 1-".$packeterror;
                $output      = shell_exec($cmd);
                $file        = $file."_2";
                $this->put_file_pcap($file);
            }
            
            $cmd    = "capinfos -aecsu ".$file;
            $output = shell_exec($cmd);
            $this->put_max_len($output);
            
            $cmd    = "tshark -r ".$file." -R '".$filter."' -T psml -n"; 
            $output = shell_exec($cmd);

            if (!preg_match('<structure>',$output)){
                $cmd    = "tshark -r ".$file." -T psml -n"; 
                $output = shell_exec($cmd);
                $this->put_filter('');
                $this->put_error(_("no data with this filter")."(".$filter.")");
            }
            
            $this->xml_psml = $output;
        }
    }
    //gets
    
    function get_file_pcap(){
        return $this->file_pcap;
    }
    function get_filter(){
        return $this->filter;
    }
    function get_psml(){
        return $this->xml_psml;
    }
    function get_pdml(){
        return $this->xml_pdml;
    }
    function get_text_bin(){
        return $this->text_bin;
    }
    function get_max_len(){
        return $this->max_len;
    }
    function get_error(){
        return $this->error;
    }
    function put_file_pcap($file){
        $this->file_pcap = $file;
    }
    function put_filter($filter){
        $this->filter = $filter;
    }
    function put_max_len($output){
        if(preg_match("/Start time:\s*([a-z]*\s[a-z]*\s*[0-9]*\s*[0-9]*\:[0-9]*\:[0-9]*\s[0-9]*)/i" , $output , $matches ))
            $this->description .= "<b>"._("Start time").":</b>".$matches[1]." ";
        if(preg_match("/End time:\s*([a-z]*\s[a-z]*\s*[0-9]*\s*[0-9]*\:[0-9]*\:[0-9]*\s[0-9]*)/i" , $output , $matches ))
            $this->description .= "<b>"._("End time").":</b>".$matches[1]." ";
        $this->description .= "<br />";
        if(preg_match("/Capture duration:\s*([0-9]*)\sseconds/" , $output , $matches )){
            $this->max_len = (int)$matches[1];
            $this->description .= "<b>"._("Capture duration").":</b>".$matches[1]." "._("seconds")." ";
        }
        if(preg_match("/Number of packets:\s*([0-9]*)/" , $output , $matches ))
            $this->description .= "<b>"._("Number of packets").":</b>".$matches[1]." ";
        if(preg_match("/File size:\s*([0-9]*)\sbytes/" , $output , $matches ))
            $this->description .= "<b>"._("File size").":</b>".$matches[1]." "._("bytes")." ";
    }
    function put_error($error){
        $this->error = $error;
    }
    
    
    //print datas
    function print_psml_head(){
        echo("
        <thead>
            <tr>
                <th class='psml' style='width: 50px; border-left:1px solid #AAAAAA;' >"._("No.")."</th>
                <th class='psml' style='width: 100px' >"._("Time")."</th>
                <th class='psml' style='width: 180px' >"._("Source")."</th>
                <th class='psml' style='width: 180px' >"._("Destination")."</th>
                <th class='psml' style='width: 100px' >"._("Protocol")."</th>
                <th class='psml' style='width: 60px' >"._("Length")."</th>
                <th class='psml'>"._("Info.")."</th>
            <tr>
        <thead>
        
        ");
        
    }
    
    function print_psml_body(){
        $psml = simplexml_load_string($this->get_psml());
        echo("<tbody>");
        $i=0;
        
        $style[0] = "width: 52px;";
        $style[1] = "width: 102px;";
        $style[2] = "width: 182px;";
        $style[3] = "width: 182px;";
        $style[4] = "width: 102px;";
        $style[5] = "width: 62px;";
        $style[6] = "";
        
        foreach ($psml->packet as $packet)
        {
            $i++;
            if ($i==1)
                $class="row1sel";
            elseif ($i%2)
                $class="row1";
            else
                $class="row2";
                
            echo("<tr class='".$class."' id='".$packet->section."'>");
            $j=0;
            foreach ($packet as $section){
                echo ("<td style='text-align:left; ".$style[$j]." '>".$section."</td>");
                $j++;
            }
            echo("</tr>");    
        }
        echo("</tbody>");
        return $packet->section[1];
        
    }
    
    function print_pdml($id){
        $cmd = "tshark -r ".$this->file_pcap." -T pdml -n frame.number==".$id; 
        //$this->xml_pdml = shell_exec($cmd);
        $xml_pdml = shell_exec($cmd);
        
        $pdml   = simplexml_load_string($xml_pdml);
        $pdml   = $pdml->packet;

        echo("<ul class='packet' id='packet' style='padding-left: 10px;' >");

        
        foreach($pdml->proto as $key => $xml_entry) {
	        $atr_tit = $xml_entry->attributes();
            if ($atr_tit['name']=="geninfo")
                continue;
            echo "<li class='section' id='section'>" . strtoupper($atr_tit['showname']) . "\n<ul style='background-color: #FFFFFF;'>\n";
            foreach($xml_entry as $key2 => $xml_entry2) {
                $atr = $xml_entry2->attributes();
                if (!preg_match("/Checksum/i",$atr['showname'])) {
                    $showname = $atr['showname'] . ": " . $atr['show'] . "";
                    echo "<li>" . $showname . "\n";
                    if (isset($xml_entry2->field)){
                        echo "<ul>\n";
                        foreach($xml_entry2->field as $key3 => $xml_entry3) {
                            $atr = $xml_entry3->attributes();
                            if (!preg_match("/Checksum/i",$atr['showname'])) {
                                //$showname = ($atr_tit['name'] == "geninfo") ? $atr['showname'] . ": " . $atr['show'] . "" : preg_replace("/(.*?):(.*)/", "\\1: \\2", $atr['showname']);
                                $showname = $atr['showname'] . ": " . $atr['show'] . "";
                                echo "<li>" . $showname . "\n";
                            }
                        }
                        echo "</ul>\n";
                    }
                }
            }
            echo "</ul>\n";
	    }
    echo("</ul>");
    }
    
    function print_text_bin($id){
        $cmd = "tshark -r ".$this->file_pcap." -T text -x -n frame.number==".$id; 
        $salida = "";
        $lines  = explode("\n",shell_exec($cmd));

        foreach ($lines as $line){
            if ($line==="")
                continue;
            $salida .= $line . "\n";
        }
        echo("<pre style='font-weight: normal !important; background-color:transparent;border: 0px;margin: 15px;font-size: 12px;font-family: Courier New;'>".$salida."</pre>");
    }
    
    function get_data_for_graphs($type){
        switch ($type)
        {
            case ("All"):
                $interval = round($this->get_max_len() / 30, 6);
                $filter = $this->get_filter() == "" ? "" : ",'".$this->get_filter()."'" ;
                $cmd = "tshark -r '".$this->get_file_pcap()."' -q -z io,stat,".$interval.",";
                $lines  = explode("\n",shell_exec($cmd));
				$values = array();
				$i=0;
				$max=0;
				foreach ($lines as $line){
					if ($line==="")
						continue;
					if (preg_match("/([0-9\.]*)-([0-9\.]*)\s*([0-9]*)\s*([0-9]*)\s*/",$line,$matches)){
						foreach($matches as $key => $match){
							if ($key == 0) continue;
							$values[$i][$key-1] = $match;
							if (($key==4) && ($max <(int)$match)) $max = (int)$match ;
						}
						$i++;
					}
				}

				foreach($values as $key => $value){
					$values[$key][6]=round((int)$value[3]*200/$max);
				}

                $nvalues = 'nvalues = [[';
                foreach($values as $value){
                    $nvalues.=$value[6].',';
                }
                $nvalues = preg_replace("/,$/","",$nvalues);
                $nvalues .= ']];';

                $realdata = 'realdata = [[';
                foreach($values as $value){
                    $realdata.=$value[3].',';
                }
                $realdata = preg_replace("/,$/","",$realdata);
                $realdata .= ']];';

                $labelx = 'labelx = ["';
                foreach($values as $value){
                    $labelx.=$value[0].'","';
                }
                $labelx = preg_replace("/,$/","",$labelx);
                $labelx .= '"];';

                echo('<script type="text/javascript">'."\n");
                echo($nvalues."\n");
                echo($labelx."\n");
                echo($realdata."\n");
                echo('</script>');
                
			break;
            
            case ("AllFilter"):
                $interval = round($this->get_max_len() / 30, 6);
                $filter = $this->get_filter() == "" ? "" : ",'".$this->get_filter()."'" ;
                $cmd = "tshark -r '".$this->get_file_pcap()."' -q -z io,stat,".$interval.",".$filter;
				$lines  = explode("\n",shell_exec($cmd));
				$values = array();
				$i=0;
				$max=0;
				foreach ($lines as $line){
					if ($line==="")
						continue;
					if (preg_match("/([0-9\.]*)-([0-9\.]*)\s*([0-9]*)\s*([0-9]*)\s*([0-9]*)\s*([0-9]*)/",$line,$matches)){
						foreach($matches as $key => $match){
							if ($key == 0) continue;
							$values[$i][$key-1] = $match;
							if (($key==4 || $key==6) && ($max <(int)$match)) $max = (int)$match ;
						}
						$i++;
					}
				}

				foreach($values as $key => $value){
					$values[$key][6]=round((int)$value[3]*200/$max);
					$values[$key][7]=round((int)$value[5]*200/$max);
				}

                $nvalues = 'nvaluesfilter = [[';
                foreach($values as $value){
                    $nvalues.=$value[6].',';
                }
                $nvalues = preg_replace("/,$/","],[",$nvalues);
                foreach($values as $value){
                    $nvalues.=$value[7].',';
                }
                $nvalues = preg_replace("/,$/","",$nvalues);
                $nvalues .= ']];';

                $realdata = 'realdatafilter = [[';
                foreach($values as $value){
                    $realdata.=$value[3].',';
                }
                $realdata = preg_replace("/,$/","],[",$realdata);
                foreach($values as $value){
                    $realdata.=$value[5].',';
                }
                $realdata = preg_replace("/,$/","",$realdata);
                $realdata .= ']];';

                $labelx = 'labelxfilter = ["';
                foreach($values as $value){
                    $labelx.=$value[0].'","';
                }
                $labelx = preg_replace("/,$/","",$labelx);
                $labelx .= '"];';

                echo('<script type="text/javascript">'."\n");
                echo($nvalues."\n");
                echo($labelx."\n");
                echo($realdata."\n");
                echo('</script>'."\n");
                
			break;
            
            case "Protocols":
                $interval = round($this->get_max_len() / 30, 6);
                $filter = $this->get_filter() == "" ? "" : ",'".$this->get_filter()."'" ;
                $cmd = "tshark -r '".$this->get_file_pcap()."' -q -z io,stat,".$interval.",tcp,udp,'not udp && not tcp'";
				$lines  = explode("\n",shell_exec($cmd));
				$values = array();
				$i=0;
				$max=0;
				foreach ($lines as $line){
					if ($line==="")
						continue;
					if (preg_match("/([0-9\.]*)-([0-9\.]*)\s*([0-9]*)\s*([0-9]*)\s*([0-9]*)\s*([0-9]*)\s*([0-9]*)\s*([0-9]*)/",$line,$matches)){
						foreach($matches as $key => $match){
							if ($key == 0) continue;
							$values[$i][$key-1] = $match;
							if (($key==4 || $key==6 || $key==8) && ($max <(int)$match)) $max = (int)$match ;
						}
						$i++;
					}
				}

				foreach($values as $key => $value){
					$values[$key][8]=round((int)$value[3]*200/$max);
					$values[$key][9]=round((int)$value[5]*200/$max);
                    $values[$key][10]=round((int)$value[7]*200/$max);
				}

                $nvalues = 'nvaluesproto = [[';
                foreach($values as $value){
                    $nvalues.=$value[8].',';
                }
                $nvalues = preg_replace("/,$/","],[",$nvalues);
                foreach($values as $value){
                    $nvalues.=$value[9].',';
                }
                $nvalues = preg_replace("/,$/","],[",$nvalues);
                foreach($values as $value){
                    $nvalues.=$value[10].',';
                }
                $nvalues = preg_replace("/,$/","",$nvalues);
                $nvalues .= ']];';

                $realdata = 'realdataproto = [[';
                foreach($values as $value){
                    $realdata.=$value[3].',';
                }
                $realdata = preg_replace("/,$/","],[",$realdata);
                foreach($values as $value){
                    $realdata.=$value[5].',';
                }
                $realdata = preg_replace("/,$/","],[",$realdata);
                foreach($values as $value){
                    $realdata.=$value[7].',';
                }
                $realdata = preg_replace("/,$/","",$realdata);
                $realdata .= ']];';

                $labelx = 'labelxproto = ["';
                foreach($values as $value){
                    $labelx.=$value[0].'","';
                }
                $labelx = preg_replace("/,$/","",$labelx);
                $labelx .= '"];';

                echo('<script type="text/javascript">'."\n");
                echo($nvalues."\n");
                echo($labelx."\n");
                echo($realdata."\n");
                echo('</script>'."\n");
                
                
            break;
            case "ProtocolsFilter":
                $interval = round($this->get_max_len() / 30, 6);
                $filter = $this->get_filter() ;
                $cmd = "tshark -r '".$this->get_file_pcap()."' -q -z io,stat,".$interval.",'tcp && ".$filter."','udp && ".$filter."','not udp && not tcp && ".$filter."',";
				$lines  = explode("\n",shell_exec($cmd));
				$values = array();
				$i=0;
				$max=0;
				foreach ($lines as $line){
					if ($line==="")
						continue;
					if (preg_match("/([0-9\.]*)-([0-9\.]*)\s*([0-9]*)\s*([0-9]*)\s*([0-9]*)\s*([0-9]*)\s*([0-9]*)\s*([0-9]*)/",$line,$matches)){
						foreach($matches as $key => $match){
							if ($key == 0) continue;
							$values[$i][$key-1] = $match;
							if (($key==4 || $key==6 || $key==8) && ($max <(int)$match)) $max = (int)$match ;
						}
						$i++;
					}
				}

				foreach($values as $key => $value){
					$values[$key][8]=round((int)$value[3]*200/$max);
					$values[$key][9]=round((int)$value[5]*200/$max);
                    $values[$key][10]=round((int)$value[7]*200/$max);
				}

                $nvalues = 'nvaluesprotofilter = [[';
                foreach($values as $value){
                    $nvalues.=$value[8].',';
                }
                $nvalues = preg_replace("/,$/","],[",$nvalues);
                foreach($values as $value){
                    $nvalues.=$value[9].',';
                }
                $nvalues = preg_replace("/,$/","],[",$nvalues);
                foreach($values as $value){
                    $nvalues.=$value[10].',';
                }
                $nvalues = preg_replace("/,$/","",$nvalues);
                $nvalues .= ']];';

                $realdata = 'realdataprotofilter = [[';
                foreach($values as $value){
                    $realdata.=$value[3].',';
                }
                $realdata = preg_replace("/,$/","],[",$realdata);
                foreach($values as $value){
                    $realdata.=$value[5].',';
                }
                $realdata = preg_replace("/,$/","],[",$realdata);
                foreach($values as $value){
                    $realdata.=$value[7].',';
                }
                $realdata = preg_replace("/,$/","",$realdata);
                $realdata .= ']];';

                $labelx = 'labelxprotofilter = ["';
                foreach($values as $value){
                    $labelx.=$value[0].'","';
                }
                $labelx = preg_replace("/,$/","",$labelx);
                $labelx .= '"];';

                echo('<script type="text/javascript">'."\n");
                echo($nvalues."\n");
                echo($labelx."\n");
                echo($realdata."\n");
                echo('</script>'."\n");
                
                
            break;
            case "sparkline":
                $interval = round($this->get_max_len() / 30, 6);
                $filter = $this->get_filter() == "" ? "" : ",'".$this->get_filter()."'" ;
                $cmd = "tshark -r '".$this->get_file_pcap()."' -q -z io,stat,".$interval.",";
                $lines  = explode("\n",shell_exec($cmd));
				$values = array();
				$i=0;
				$max=0;
				foreach ($lines as $line){
					if ($line==="")
						continue;
					if (preg_match("/([0-9\.]*)-([0-9\.]*)\s*([0-9]*)\s*([0-9]*)\s*/",$line,$matches)){
						foreach($matches as $key => $match){
							if ($key == 0) continue;
							$values[$i][$key-1] = $match;
							if (($key==4) && ($max <(int)$match)) $max = (int)$match ;
						}
						$i++;
					}
				}

				foreach($values as $key => $value){
					$values[$key][6]=round((int)$value[3]*200/$max);
				}

                $nvalues = '';
                foreach($values as $value){
                    $nvalues.=$value[6].',';
                }
                $nvalues = preg_replace("/,$/","",$nvalues);
                $nvalues .= '';
                return $nvalues;
             break;
        }
        echo('<script src="js/statistics.js" type="text/javascript"></script>'."\n");
        
    }
}

function checkfilter($filter){
    $not_permited='/[^0-9a-zA-Z\.\|\&\=\<\>\!\^]/';
    $filter      =preg_replace($not_permited, "", $filter);
    return $filter;
}