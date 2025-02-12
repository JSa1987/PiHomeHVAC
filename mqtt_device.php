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
require_once(__DIR__.'/st_inc/session.php');
confirm_logged_in();
require_once(__DIR__.'/st_inc/connection.php');
require_once(__DIR__.'/st_inc/functions.php');

$gw_script_txt = 'python3 /var/www/cron/gateway.py';

$date_time = date('Y-m-d H:i:s');

if(isset($_GET['id'])) {
	$id = $_GET['id'];
} else {
	$id = 0;
}
//Form submit
if (isset($_POST['submit'])) {
	$mqtt_child_id = intval($_POST['child_id']);
	$mqtt_type_id = $_POST['type'];
    if ($mqtt_type_id == 0) { $node_name = "MQTT Sensor"; } else { $node_name = "MQTT Controller"; }
    $query = "SELECT id FROM nodes WHERE name = '{$node_name}' LIMIT 1;";
    $result = $conn->query($query);
    $found_product = mysqli_fetch_array($result);
    $nodes_id = $found_product['id'];
    $purge= '0';
	$mqtt_name = $_POST['mqtt_name'];
    $mqtt_topic = $_POST['mqtt_topic'];
    $mqtt_on_message = $_POST['on_message'];
    $mqtt_off_message = $_POST['off_message'];
    $mqtt_json_attribute = $_POST['json_attribute'];

	//Add or Edit MQTT Device record to mqtt_devices Table
	if ($id == 0) {
		$query = "INSERT INTO `mqtt_devices`(`id`, `child_id`, `nodes_id`, `type`, `purge`, `name`, `mqtt_topic`, `on_payload`, `off_payload`, `attribute`)  VALUES ('{$id}', '{$mqtt_child_id}', '{$nodes_id}', {$mqtt_type_id}, '0', '{$mqtt_name}', '{$mqtt_topic}', '{$mqtt_on_message}', '{$mqtt_off_message}', '{$mqtt_json_attribute}');";
	} else {
	        $query = "UPDATE `mqtt_devices` SET `child_id`= '{$mqtt_child_id}',`nodes_id`= '{$nodes_id}',`type`= '{$mqtt_type_id}',`purge`= '{$purge}',`name`= '{$mqtt_name}',`mqtt_topic`= '{$mqtt_topic}',`on_payload`= '{$mqtt_on_message}',`off_payload`= '{$mqtt_off_message}',`attribute`= '{$mqtt_json_attribute}' WHERE `id` = '{$id}';";
	}
	$result = $conn->query($query);
        $temp_id = mysqli_insert_id($conn);
	if ($result) {
                if ($id==0){
                        $message_success = "<p>".$lang['mqtt_device_record_add_success']."</p>";
                } else {
                        $message_success = "<p>".$lang['mqtt_device_record_update_success']."</p>";
                }
	} else {
		$error = "<p>".$lang['mqtt_device_record_fail']." </p> <p>" .mysqli_error($conn). "</p>";
	}
	if ($id == 0) {
		$query = "SELECT `child_id` FROM `mqtt_devices` WHERE `type` = '{$mqtt_type_id}' ORDER BY `child_id` DESC LIMIT 1";
		$result = $conn->query($query);
        $found_product = mysqli_fetch_array($result);
        $max_child_id = $found_product['child_id'];
		$query = "UPDATE nodes SET `max_child_id` = '{$max_child_id}' WHERE `id` = '{$nodes_id}';";
		$result = $conn->query($query);
        $temp_id = mysqli_insert_id($conn);
		if ($result) {
			$message_success .=  "<p>".$lang['mqtt_max_child_success']."</p>";
		} else {
			$error .= "<p>".$lang['mqtt_max_child_fail']." </p> <p>" .mysqli_error($conn). "</p>";
		}
	} 
	if ($mqtt_type_id == 0) {
		$query = "UPDATE gateway SET reboot = '1' LIMIT 1;";
		$conn->query($query);
		$temp_id = mysqli_insert_id($conn);
		if ($result) {
			$message_success .=  "<p>".$lang['mqtt_gateway_reboot_success']."</p>";
		} else {
			$error .= "<p>".$lang['mqtt_gateway_reboot_fail']." </p> <p>" .mysqli_error($conn). "</p>";
		}
	} 
	$message_success .= "<p>".$lang['do_not_refresh']."</p>";
	header("Refresh: 10; url=home.php");
	// After update on all required tables, set $id to mysqli_insert_id.
	if ($id==0){$id=$temp_id;}
}
?>
<!-- ### Visible Page ### -->
<?php include("header.php");  ?>
<?php include_once("notice.php"); ?>

<!-- Don't display form after submit -->
<?php if (!(isset($_POST['submit']))) { ?>

<!-- If the request is to EDIT, retrieve selected items from DB   -->
<!-- If the request is to ADD, find the new child ID   -->
<?php if ($id != 0) {
    $query = "SELECT * FROM `mqtt_devices` WHERE `id` = {$id} limit 1;";
	$result = $conn->query($query);
	$row = mysqli_fetch_assoc($result);

	$query = "SELECT * FROM nodes WHERE id = '{$row['nodes_id']}' LIMIT 1;";
	$result = $conn->query($query);
	$rownode = mysqli_fetch_assoc($result);
} else {
	$query = "SELECT child_id FROM mqtt_devices WHERE type = '0' ORDER BY child_id ASC;";
	$results = $conn->query($query);
	$new_child_row = mysqli_fetch_assoc($results);
	$new_child_id_sensor =  $new_child_row["child_id"] + 1;
	while ($new_child_row = mysqli_fetch_assoc($results)) {
		if ($new_child_row["child_id"] == $new_child_id_sensor) {
			$new_child_id_sensor = $new_child_id_sensor +1;
		}
	}
	$query = "SELECT child_id FROM mqtt_devices WHERE type = '1' ORDER BY child_id ASC;";
	$results = $conn->query($query);
	$new_child_row = mysqli_fetch_assoc($results);
	$new_child_id_controller =  $new_child_row["child_id"] + 1;
	while ($new_child_row = mysqli_fetch_assoc($results)) {
		if ($new_child_row["child_id"] == $new_child_id_controller) {
			$new_child_id_controller = $new_child_id_controller +1;
		}
	}
}
?>

<!-- Title (e.g. Add Sensor or Edit Sensor) -->
<div id="page-wrapper">
	<br>
	<div class="row">
        	<div class="col-lg-12">
                	<div class="panel panel-primary">
                        	<div class="panel-heading">
					<?php if ($id != 0) { echo $lang['mqtt_edit_device'] . ": " . $row['name']; }else{
                            		echo "<i class=\"fa fa-plus fa-1x\"></i>" ." ". $lang['mqtt_add_device'];} ?>
					<div class="pull-right">
						<div class="btn-group"><?php echo date("H:i"); ?></div>
					</div>
                        	</div>
                        	<!-- /.panel-heading -->
				<div class="panel-body">
					<form data-toggle="validator" role="form" method="post" action="<?php $_SERVER['PHP_SELF'];?>" id="form-join">
						<!-- Node Type -->
						<div class="form-group" class="control-label" style="display:block"><label><?php echo $lang['node_type']; ?></label> <small class="text-muted"><?php echo $lang['mqtt_node_type_info'];?></small>
							<select class="form-control select2" type="number" id="type" name="type" onchange=sensor_controller(this.options[this.selectedIndex].value)>
							<option></option>
							<?php if ($id != 0) {
								echo '<option selected value='.$row['type'].'>'.$rownode['name'].'</option>';
							} else {
								echo '<option selected value=0>MQTT Sensor</option>
								<option value=1>MQTT Controller</option>';
							} ?>
							</select>
							<div class="help-block with-errors"></div>
						</div>

                                                <script language="javascript" type="text/javascript">
                                                function sensor_controller(value)
                                                {
                                                        var valuetext = value;
                                                        if (valuetext == 0) {
                                                                document.getElementById("on_message").style.display = 'none';
                                                                document.getElementById("on_message_label").style.visibility = 'hidden';
                                                                document.getElementById("off_message").style.display = 'none';
                                                                document.getElementById("off_message_label").style.visibility = 'hidden';
                                                                document.getElementById("json_attribute").style.display = 'block';
                                                                document.getElementById("json_attribute_label").style.visibility = 'visible';
																document.getElementById("child_id").value = "<?php if ($id != 0) { echo $row["child_id"]; } else { echo $new_child_id_sensor; } ?>";
                                                        } else {
                                                                document.getElementById("on_message").style.display = 'block';
                                                                document.getElementById("on_message_label").style.visibility = 'visible';
                                                                document.getElementById("off_message").style.display = 'block';
                                                                document.getElementById("off_message_label").style.visibility = 'visible';
                                                                document.getElementById("json_attribute").style.display = 'none';
                                                                document.getElementById("json_attribute_label").style.visibility = 'hidden';
																document.getElementById("child_id").value = "<?php if ($id != 0) { echo $row["child_id"]; } else { echo $new_child_id_controller; } ?>";
                                                        }
                                                }
                                                </script>

                                                <!-- MQTT Name -->
					                <div class="form-group" class="control-label"><label><?php echo $lang['mqtt_name']; ?></label> <small class="text-muted"><?php echo $lang['mqtt_name_info'];?></small>
                                                        <input class="form-control" placeholder="eg. Bathroom_TRV_Temp" value="<?php if(isset($row['name'])){ echo $row['name']; } ?>" id="mqtt_name" name="mqtt_name" data-error="<?php echo $lang['mqtt_name_help']; ?>" autocomplete="off" required>
                                                        <div class="help-block with-errors"></div>
                                                </div>

						<!-- MQTT Device Child ID   -->
                                                	<div class="form-group" class="control-label"><label><?php echo $lang['mqtt_child_id'];?></label> <small class="text-muted"><?php echo $lang['mqtt_child_id_info'];?></small>
							<input class="form-control input-sm" type="text" value="<?php if(isset($row['name'])){ echo $row['child_id']; } else { echo $new_child_id_sensor; }?>" id="child_id" name="child_id" readonly="readonly" data-error="<?php echo $lang['mqtt_child_id_error']; ?>" autocomplete="off" required>
                                                        <div class="help-block with-errors"></div>
                                                </div>

						<!-- MQTT Topic -->
							<div class="form-group" class="control-label"><label><?php echo $lang['mqtt_topic']; ?></label> <small class="text-muted"><?php echo $lang['mqtt_topic_info'];?></small>
							<input class="form-control" placeholder="eg. zigbee2mqtt/Bathroom_TRV" value="<?php if(isset($row['mqtt_topic'])) { echo $row['mqtt_topic']; } ?>" id="mqtt_topic" name="mqtt_topic" data-error="<?php echo $lang['mqtt_topic_help']; ?>" autocomplete="off" required>
							<div class="help-block with-errors"></div>
						</div>

                                                <!-- JSON Attribute -->
							<div class="form-group" class="control-label" id="json_attribute_label" style="display:block"><label><?php echo $lang['mqtt_json_attribute']; ?></label> <small class="text-muted"><?php echo $lang['mqtt_json_attribute_info'];?></small>
                                                        <input class="form-control" placeholder="eg. local_temperature" value="<?php if(isset($row['attribute'])) { echo $row['attribute']; } ?>" id="json_attribute" name="json_attribute" data-error="<?php echo $lang['mqtt_json_attribute_help']; ?>" autocomplete="off">
                                                        <div class="help-block with-errors"></div>
                                                </div>

                                                <!-- ON Message -->
							<div class="form-group" class="control-label" id="on_message_label" style="display:block"><label><?php echo $lang['mqtt_on_message']; ?></label> <small class="text-muted"><?php echo $lang['mqtt_on_message_info'];?></small>
                                                        <input class="form-control" placeholder='eg. {"force": "open"}' value='<?php if(isset($row['on_payload'])) { echo $row['on_payload']; } ?>' id="on_message" name="on_message" data-error="<?php echo $lang['mqtt_on_message_help']; ?>" autocomplete="off" required>
                                                        <div class="help-block with-errors"></div>
                                                </div>

                                                <!-- OFF Message -->
							<div class="form-group" class="control-label" id="off_message_label" style="display:block"><label><?php echo $lang['mqtt_off_message']; ?></label> <small class="text-muted"><?php echo $lang['mqtt_off_message_info'];?></small>
                                                        <input class="form-control" placeholder='eg. {"force": "close"}' value='<?php if(isset($row['off_payload'])) { echo $row['off_payload']; } ?>' id="off_message" name="off_message" data-error="<?php echo $lang['mqtt_off_message_help']; ?>" autocomplete="off" required>
                                                        <div class="help-block with-errors"></div>
                                                </div>

						<br>
						<!-- Buttons -->
						<input type="submit" name="submit" value="<?php echo $lang['submit']; ?>" class="btn btn-default btn-sm">
						<a href="home.php"><button type="button" class="btn btn-primary btn-sm"><?php echo $lang['cancel']; ?></button></a>
					</form>
				</div>
                		<!-- /.panel-body -->
				<div class="panel-footer">
					<?php
					echo '<script type="text/javascript">',
     					'sensor_controller("'.$row['type'].'");',
     					'</script>'
					;
					ShowWeather($conn);
					?>
                        		<div class="pull-right">
                        			<div class="btn-group">
                        			</div>
					</div>
				</div>
				<!-- /.panel-footer -->
			</div>
			<!-- /.panel panel-primary -->
		</div>
                <!-- /.col-lg-4 -->
	</div>
        <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php }  ?>
<?php include("footer.php");  ?>

