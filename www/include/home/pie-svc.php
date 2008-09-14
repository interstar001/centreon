<?php
/*
 * Centreon is developped with GPL Licence 2.0 :
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * Developped by : Julien Mathis - Romain Le Merlus 
 * 
 * The Software is provided to you AS IS and WITH ALL FAULTS.
 * Centreon makes no representation and gives no warranty whatsoever,
 * whether express or implied, and without limitation, with regard to the quality,
 * any particular or intended purpose of the Software found on the Centreon web site.
 * In no event will Centreon be liable for any direct, indirect, punitive, special,
 * incidental or consequential damages however they may arise and even if Centreon has
 * been previously advised of the possibility of such damages.
 * 
 * For information : contact@centreon.com
 */
 
	require_once ("../../class/Session.class.php");
	require_once ("../../class/Oreon.class.php");
	
	Session::start();
	$oreon =& $_SESSION["oreon"];

	require_once("DB.php");
	include_once("@CENTREON_ETC@/centreon.conf.php");	
		
	/* Connect to oreon DB */
	$dsn = array('phptype'  => 'mysql',
			     'username' => $conf_centreon['user'],
			     'password' => $conf_centreon['password'],
			     'hostspec' => $conf_centreon['hostCentreon'],
			     'database' => $conf_centreon['db'],);	
	$options = array('debug'=> 2, 'portability' => DB_PORTABILITY_ALL ^ DB_PORTABILITY_LOWERCASE,);	

	$pearDB =& DB::connect($dsn, $options);
	if (PEAR::isError($pearDB)) die("Connecting problems with oreon database : " . $pearDB->getMessage());
	$pearDB->setFetchMode(DB_FETCHMODE_ASSOC);

	include_once($centreon_path . "www/include/common/common-Func-ACL.php");
	include_once($centreon_path . "www/include/common/common-Func.php");

	$ndo_base_prefix = getNDOPrefix();
	
	include_once($centreon_path . "www/DBNDOConnect.php");

	## calcul stat for resume
	$statistic = array(0 => "OK", 1 => "WARNING", 2 => "CRITICAL", 3 => "UNKNOWN", 4 => "PENDING");
	
        /*
         * LCA
         */

        $sid = session_id();
        $res1 =& $pearDB->query("SELECT user_id FROM session WHERE session_id = '$sid'");
        $user = $res1->fetchRow();
        $user_id = $user["user_id"];

        $res2 =& $pearDB->query("SELECT contact_admin FROM contact WHERE contact_id = '".$user_id."'");
        $admin = $res2->fetchrow();

        global $is_admin;

        $is_admin = isUserAdmin($_GET["sid"]);

        if (!$is_admin){
                $_POST["sid"] = $_GET["sid"];
        }

	/* Get ServiceNDO status */
	if(!$is_admin){

	$DBRESULT =& $pearDB->query("SELECT acl_group_id FROM acl_group_contacts_relations WHERE acl_group_contacts_relations.contact_contact_id = '$user_id'");
        $DBRESULT_1 = $DBRESULT->fetchRow();
        $acl_group_id = $DBRESULT_1["acl_group_id"];

	$DBRESULT =& $pearDB->query("SELECT acl_res_id FROM acl_res_group_relations, acl_groups WHERE acl_res_group_relations.acl_group_id = '$acl_group_id' AND acl_groups.acl_group_activate = '1'");
        $DBRESULT_1 = $DBRESULT->fetchRow();
        $acl_res_id = $DBRESULT_1["acl_res_id"];

        $DBRESULT =& $pearDB->query("SELECT host_host_id FROM acl_resources_host_relations WHERE acl_res_id = '$acl_res_id'");
        while($DBRESULT_1 = $DBRESULT->fetchRow()){
                $host_host_id = $DBRESULT_1["host_host_id"];
		$DBRESULT_2 =& $pearDB->query("SELECT host_name from host WHERE host_id = '$host_host_id'");
		$DBRESULT_3 = $DBRESULT_2->fetchRow();
		$host_name = $DBRESULT_3["host_name"];

			$rq1 = "SELECT count(nss.current_state) as cnt, nss.current_state" .
							" FROM ".$ndo_base_prefix."servicestatus nss, ".$ndo_base_prefix."objects no" .
							" WHERE no.object_id = nss.service_object_id".
							" AND no.name1 not like 'OSL_Module'".
							" AND no.name1 = '$host_name'".
							" AND no.is_active = 1 GROUP BY nss.current_state ORDER by nss.current_state";
	}
	}
	else
	{
	$rq1 = "SELECT count(nss.current_state) as cnt, nss.current_state" .
			" FROM ".$ndo_base_prefix."servicestatus nss, ".$ndo_base_prefix."objects no" .
			" WHERE no.object_id = nss.service_object_id".
			" AND no.name1 not like 'OSL_Module'".
			" AND no.is_active = 1 GROUP BY nss.current_state ORDER by nss.current_state";
	}

	$DBRESULT_NDO1 =& $pearDBndo->query($rq1);
	if (PEAR::isError($DBRESULT_NDO1))
		print "DB Error : ".$DBRESULT_NDO1->getDebugInfo()."<br />";
	$data = array();
	$color = array();
	$counter = 0;
	while ($ndo =& $DBRESULT_NDO1->fetchRow()){
		$data[] = $ndo["cnt"];
		$legend[] = $statistic[$ndo["current_state"]];
		$color[] = $oreon->optGen["color_".strtolower($statistic[$ndo["current_state"]])];		
		$counter += $ndo["cnt"];
	}
	$DBRESULT_NDO1->free();
	
	foreach ($data as $key => $value)
		$data[$key] = round($value / $counter * 100, 2);
	
	
	/*
	 *  create the dataset
	 */
	
	include_once($centreon_path.'/www/lib/ofc-library/open-flash-chart.php' );
	$g = new graph();
	$g->bg_colour = '#F3F6F6';
	//
	// PIE chart, 60% alpha
	//
	$g->pie(60,'#505050','#000000');
	//
	// pass in two arrays, one of data, the other data labels
	//
	
	$g->pie_values($data, $legend);
	//
	// Colours for each slice, in this case some of the colours
	// will be re-used (3 colurs for 5 slices means the last two
	// slices will have colours colour[0] and colour[1]):
	//
	$g->set_tool_tip( '#key# : #val# %' );
	
	$g->pie_slice_colours($color);

	$g->set_tool_tip( '#val#%' );
	$g->title( _('Services'), '{font-size:18px; color: #424242}' );
	echo $g->render();

?>
