<?php
/*
             __  __                             _
            |  \/  |                    /\     (_)
            | \  / |   __ _  __  __    /  \     _   _ __
            | |\/| |  / _` | \ \/ /   / /\ \   | | |  __|
            | |  | | | (_| |  >  <   / ____ \  | | | |
            |_|  |_|  \__,_| /_/\_\ /_/    \_\ |_| |_|
                    S M A R T   T H E R M O S T A T
*************************************************************************"
* MaxAir is a Linux based Central Heating Control systems. It runs from *"
* a web interface and it comes with ABSOLUTELY NO WARRANTY, to the      *"
* extent permitted by applicable law. I take no responsibility for any  *"
* loss or damage to you or your property.                               *"
* DO NOT MAKE ANY CHANGES TO YOUR HEATING SYSTEM UNTILL UNLESS YOU KNOW *"
* WHAT YOU ARE DOING                                                    *"
*************************************************************************"
*/
echo "<h4>".$lang['graph_battery_usage']."</h4></p>".$lang['graph_battery_level_text']."</p>";

;?>
<div class="flot-chart">
   <div class="flot-chart-content" id="battery_level"></div>
</div>
<br>
<script type="text/javascript">
// create battery usage dataset based on all available zones
var bat_level_dataset = [
<?php
    $querya ="select * from nodes where `type` = 'MySensor' AND `name` LIKE '%Sensor' AND `min_value` <> 0;";
    $resulta = $conn->query($querya);
    $counter = 0;
    $count = mysqli_num_rows($resulta) + 1;
    while ($row = mysqli_fetch_assoc($resulta)) {
        //grab the node id to be displayed in the plot legend
                $id=$row['id'];
                $node_id=$row['node_id'];
                $query="select * from sensors where sensor_id = '{$id}' limit 1;";
                $result_ts = $conn->query($query);
                $temp_sensor_row = mysqli_fetch_array($result_ts);
                $name = $temp_sensor_row['name'];
                $label = $name ." - ID ".$id;
                $graph_id = $id.".0"; //assume battery node colour same as child_id = 0
		$query="SELECT bat_voltage, bat_level, `update`  FROM nodes_battery WHERE `update` >= last_day(now()) + interval 1 day - interval 3 MONTH AND bat_level is not NULL and node_id = '{$node_id}' GROUP BY Week(`update`), Day(`update`) ORDER BY `update` ASC;";
        	$result = $conn->query($query);
        	// create array of pairs of x and y values for every zone
        	$bat_level = array();
        	while ($rowb = mysqli_fetch_assoc($result)) {
            		$bat_level[] = array(strtotime($rowb['update']) * 1000, $rowb['bat_level']);
        	}
        	// create dataset entry using distinct color based on zone index(to have the same color everytime chart is opened)
        	echo "{label: \"".$label."\", data: ".json_encode($bat_level).", color: '".$sensor_color[$graph_id]."'}, \n";
    }
?> ];
</script>
