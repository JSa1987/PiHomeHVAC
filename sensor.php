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

$date_time = date('Y-m-d H:i:s');

if(isset($_GET['id'])) {
	$id = $_GET['id'];
} else {
	$id = 0;
}
//Form submit
if (isset($_POST['submit'])) {
        $pre_post = isset($_POST['pre_post']) ? $_POST['pre_post'] : "0";
        $index_id = $_POST['index_id'];
	$name = $_POST['name'];
	$sensor_type_id = $_POST['type'];
	$node_id = $_POST['selected_sensor_id'];
        $query = "SELECT id FROM nodes WHERE node_id = '{$node_id}' LIMIT 1;";
        $result = $conn->query($query);
        $found_product = mysqli_fetch_array($result);
        $sensor_id = $found_product['id'];
	$sensor_child_id = $_POST['sensor_child_id'];
        $correction_factor = $_POST['correction_factor'];
        $sync = '0';
        $purge= '0';
	$frost_temp = $_POST['frost_temp'];
	if ($frost_temp == 0) { $frost_controller = 0; } else { $frost_controller = $_POST['frost_controller']; }

	//Add or Edit Sensor record to sensors Table
	$query = "INSERT INTO `sensors` (`id`, `sync`, `purge`, `zone_id`, `sensor_id`, `sensor_child_id`, `correction_factor`, `sensor_type_id`, `index_id`, `pre_post`, `name`, `graph_num`, `show_it`, `frost_temp`, `frost_controller`) VALUES ('{$id}', '{$sync}', '{$purge}', '0', '{$sensor_id}', '{$sensor_child_id}', '{$correction_factor}', '{$sensor_type_id}', '{$index_id}', '{$pre_post}', '{$name}', '0', '1', '{$frost_temp}', '{$frost_controller}') ON DUPLICATE KEY UPDATE sync=VALUES(sync), `purge`=VALUES(`purge`), sensor_id=VALUES(sensor_id), sensor_child_id=VALUES(sensor_child_id), correction_factor=VALUES(correction_factor), sensor_type_id=VALUES(sensor_type_id), index_id=VALUES(index_id), pre_post=VALUES(pre_post), name=VALUES(name), graph_num=VALUES(graph_num), show_it=VALUES(show_it), frost_temp=VALUES(frost_temp), frost_controller=VALUES(frost_controller);";
	$result = $conn->query($query);
        $temp_id = mysqli_insert_id($conn);
	if ($result) {
                if ($id==0){
                        $message_success = "<p>".$lang['temp_sensor_record_add_success']."</p>";
                } else {
                        $message_success = "<p>".$lang['temp_sensor_record_update_success']."</p>";
                }
	} else {
		$error = "<p>".$lang['temp_sensor_record_fail']." </p> <p>" .mysqli_error($conn). "</p>";
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
<?php if ($id != 0) {
        $query = "SELECT * FROM `sensors` WHERE `id` = {$id} limit 1;";
	$result = $conn->query($query);
	$row = mysqli_fetch_assoc($result);

	$query = "SELECT * FROM nodes WHERE id = '{$row['sensor_id']}' LIMIT 1;";
	$result = $conn->query($query);
	$rownode = mysqli_fetch_assoc($result);

        $query = "SELECT id, name FROM relays WHERE id = '{$row['frost_controller']}' LIMIT 1;";
        $result = $conn->query($query);
        $rowcontroller = mysqli_fetch_assoc($result);

        $query = "SELECT id, type FROM sensor_type WHERE id = '{$row['sensor_type_id']}' LIMIT 1;";
        $result = $conn->query($query);
        $rowtype = mysqli_fetch_assoc($result);
}
?>

<!-- Title (e.g. Add Sensor or Edit Sensor) -->
<div id="page-wrapper">
	<br>
	<div class="row">
        	<div class="col-lg-12">
                	<div class="panel panel-primary">
                        	<div class="panel-heading">
					<?php if ($id != 0) { echo $lang['sensor_edit'] . ": " . $row['name']; }else{
                            		echo "<i class=\"fa fa-plus fa-1x\"></i>" ." ". $lang['sensor_add'];} ?>
					<div class="pull-right"> 
						<div class="btn-group"><?php echo date("H:i"); ?></div>
					</div>
                        	</div>
                        	<!-- /.panel-heading -->
				<div class="panel-body">
					<form data-toggle="validator" role="form" method="post" action="<?php $_SERVER['PHP_SELF'];?>" id="form-join">
						<!-- Before or After System Controller Icon -->
						<div class="checkbox checkbox-default checkbox-circle">
							<input id="checkbox0" class="styled" type="checkbox" name="pre_post" value="1" <?php $check = ($row['pre_post'] == 1) ? 'checked' : ''; echo $check; ?>>>
							<label for="checkbox0"> <?php echo $lang['pre_sc_tile']; ?> </label> <small class="text-muted"><?php echo $lang['pre_sc_tile_info'];?></small>
							<div class="help-block with-errors"></div>
						</div>

						<!-- Index Number -->
						<?php
						$query = "select index_id from sensors order by index_id desc limit 1;";
						$result = $conn->query($query);
						$found_product = mysqli_fetch_array($result);
						$new_index_id = $found_product['index_id']+1;
						?>
						<div class="form-group" class="control-label"><label><?php echo $lang['temp_sensor_index_number']; ?>  </label> <small class="text-muted"><?php echo $lang['temp_sensor_index_number_info'];?></small>
							<input class="form-control" placeholder="<?php echo $lang['temp_sensor_index_number']; ?>r" value="<?php if(isset($row['index_id'])) { echo $row['index_id']; }else {echo $new_index_id; }  ?>" id="index_id" name="index_id" data-error="<?php echo $lang['temp_sensor_index_number_help']; ?>" autocomplete="off" required>
							<div class="help-block with-errors"></div>
						</div>

						<!-- Sensor Type -->
						<div class="form-group" class="control-label" style="display:block"><label><?php echo $lang['sensor_type']; ?></label> <small class="text-muted"><?php echo $lang['sensor_type_info'];?></small>
							<select class="form-control select2" type="number" id="type" name="type" onchange=enable_frost_temp(this.options[this.selectedIndex].value)>

							<?php if(isset($rowtype['type'])) { 
								echo '<option selected value='.$rowtype['id'].'>'.$rowtype['type'].'</option>'; 
							} else {
								echo '<option selected value=1>'.$lang['temperature'].'</option>';
							} ?>
							<?php  $query = "SELECT DISTINCT `id`, `type` FROM `sensor_type` ORDER BY `id` ASC;";
							$result = $conn->query($query);
							echo "<option></option>";
							while ($datarw=mysqli_fetch_array($result)) {
        							echo "<option value=".$datarw['id'].">".$datarw['type']."</option>";
							} ?>
							</select>
							<div class="help-block with-errors"></div>
						</div>

                                                <script language="javascript" type="text/javascript">
                                                function enable_frost_temp(value)
                                                {
                                                        var valuetext = value;
                                                        if (valuetext == 2) {
                                                                document.getElementById("frost_temp").style.display = 'none';
                                                                document.getElementById("frost_protection_label").style.visibility = 'hidden';
                                                        } else {
                                                                document.getElementById("frost_temp").style.display = 'block';
                                                                document.getElementById("frost_protection_label").style.visibility = 'visible';
                                                        }
                                                }
                                                </script>

						<!-- Sensor Name -->
						<div class="form-group" class="control-label"><label><?php echo $lang['sensor_name']; ?></label> <small class="text-muted"><?php echo $lang['sensor_name_info'];?></small>
							<input class="form-control" placeholder="Temperature Sensor Name" value="<?php if(isset($row['name'])) { echo $row['name']; } ?>" id="name" name="name" data-error="<?php echo $lang['sensor_name_help']; ?>" autocomplete="off" required>
							<div class="help-block with-errors"></div>
						</div>

						<!-- Temperature Sensor ID -->
						<div class="form-group" class="control-label" id="sensor_id_label" style="display:block"><label><?php echo $lang['sensor_id']; ?></label> <small class="text-muted"><?php echo $lang['zone_sensor_id_info'];?></small>
							<select id="sensor_id" onchange=SensorChildList(this.options[this.selectedIndex].value) name="sensor_id" class="form-control select2" data-error="<?php echo $lang['sensor_id_error']; ?>" autocomplete="off" required>
                                                                <?php if(isset($rownode['node_id'])) {
                                                                        echo '<option selected >'.$rownode['node_id'].' - '.$rownode['name'].'</option>';
                                                			$query = "SELECT node_id, name, max_child_id FROM nodes where (name LIKE '%Sensor' OR name LIKE 'Switch%')AND id <> ".$rownode['id']." ORDER BY node_id ASC;";
                                                                } else {
                                                                        $query = "SELECT node_id, name, max_child_id FROM nodes where name LIKE '%Sensor' ORDER BY node_id ASC;";
                                                                }
								$result = $conn->query($query);
								echo "<option></option>";
								while ($datarw=mysqli_fetch_array($result)) {
        								if(strpos($datarw['name'], 'Add-On') !== false) { $max_child_id = 0; } else { $max_child_id = $datarw['max_child_id']; }
									echo "<option value=".$datarw['max_child_id'].">".$datarw['node_id']." - ".$datarw['name']."</option>";
								} ?>
							</select>
							<div class="help-block with-errors"></div>
						</div>

						<script language="javascript" type="text/javascript">
						function SensorChildList(value) {
        						var valuetext = value;
							var e = document.getElementById("sensor_id");
							var selected_sensor_id = e.options[e.selectedIndex].text;
							var selected_sensor_id = selected_sensor_id.split(" - ");

							document.getElementById("selected_sensor_id").value = selected_sensor_id[0];

        						var opt = document.getElementById("sensor_child_id").getElementsByTagName("option");
        						for(j=opt.length-1;j>=0;j--)
        						{
                						document.getElementById("sensor_child_id").options.remove(j);
        						}
	        					for(j=0;j<=valuetext;j++)
        						{
                						var optn = document.createElement("OPTION");
                						optn.text = j;
                						optn.value = j;
                						document.getElementById("sensor_child_id").options.add(optn);
        						}
						}
						</script>

						<input type="hidden" id="selected_sensor_id" name="selected_sensor_id" value="<?php echo $rownode['node_id']?>"/>
						<input type="hidden" id="graph_num" name="graph_num" value="<?php echo $row['graph_num']?>"/>
						<input type="hidden" id="show_it" name="show_it" value="<?php echo $row['show_it']?>"/>

						<!-- Temperature Sensor Child ID -->
						<div class="form-group" class="control-label" id="sensor_child_id_label" style="display:block"><label><?php echo $lang['sensor_child_id']; ?></label> <small class="text-muted"><?php echo $lang['sensor_child_id_info'];?></small>
							<select id="sensor_child_id" name="sensor_child_id" class="form-control select2" data-error="<?php echo $lang['sensor_child_id_error']; ?>" autocomplete="off" required>
								<?php if(isset($row['sensor_child_id'])) { echo '<option selected >'.$row['sensor_child_id'].'</option>';
								for ($x = 0; $x <= $rownode['max_child_id']; $x++) {
        								echo "<option value=".$x.">".$x."</option>";
        							}
							} ?>
							</select>
							<div class="help-block with-errors"></div>
						</div>

                                                <!-- Correction Factor -->
                                                <div class="form-group" class="control-label"><label><?php echo $lang['sensor_correction_factor']; ?></label> <small class="text-muted"><?php echo $lang['sensor_correction_factor_info'];?></small>
                                                        <input class="form-control" placeholder="Temperature Sensor Correction Factor" value="<?php if(isset($row['name'])) { echo $row['correction_factor']; } else { echo '0.00'; } ?>" id="correction_factor" name="correction_factor" data-error="<?php echo $lang['sensor_correction_factor_help']; ?>" autocomplete="off" required>
                                                        <div class="help-block with-errors"></div>
                                                </div>

						<!-- Frost Temperature -->
						<div class="form-group" class="control-label" id="frost_protection_label" style="display:block"><label><?php echo $lang['frost_protection']; ?></label> <small class="text-muted"><?php echo $lang['frost_protection_text'];?></small>
							<select class="form-control select2" type="number" id="frost_temp" name="frost_temp" onchange=enable_frost()>
                						<?php if(isset($row['frost_temp'])) { 
									echo '<option selected >'.$row['frost_temp'].'</option>';
								} else {
									echo '<option selected >0</option>';
								} ?>
								<?php $c_f = settings($conn, 'c_f');
								echo "<option></option>";
								if($c_f==1 || $c_f=='1') {
                							for($t=28;$t<=50;$t++){
                        							echo "<option value=".$t.">".$t."</option>";
                        						}
								} else {
                							for($t=-1;$t<=12;$t++) {
                        							echo "<option value=".$t.">".$t."</option>";
                							}
	                					} ?>
							</select>
							<div class="help-block with-errors"></div>
						</div>

						<script language="javascript" type="text/javascript">
						function enable_frost()
						{
							var t = document.getElementById("frost_temp").value;
        						if (t == 0) {
        							document.getElementById("frost_controller").style.display = 'none';
               							document.getElementById("frost_controller_label").style.visibility = 'hidden';
							} else {
                                                                document.getElementById("frost_controller").style.display = 'block';
                                                                document.getElementById("frost_controller_label").style.visibility = 'visible';
							}
						}
						</script>
						<!-- Frost Controller -->
						<div class="form-group"  id="frost_controller_label" style="display:block"><label><?php echo $lang['frost_controller']; ?></label> <small class="text-muted"><?php echo $lang['frost_controller_text'];?></small>
                                                        <select class="form-control select2" type="number" id="frost_controller" name="frost_controller" >
                                                                <?php if(isset($rowcontroller['id'])) {
                                                                        echo '<option selected value='.$rowcontroller['id'].'>'.$rowcontroller['name'].'</option>';
                                                        		$query = "SELECT id, name, type FROM relays WHERE type <> 1 AND id <> ".$rowcontroller['id']." ORDER BY id ASC;";
                                                                } else {
                                                                        $query = "SELECT id, name, type FROM relays WHERE type <> 1 ORDER BY id ASC;";
                                                                }
                                                                $result = $conn->query($query);
                                                                while ($datarw=mysqli_fetch_array($result)) {
                                                                        echo "<option value=".$datarw['id'].">".$datarw['name']."</option>";
                                                                } ?>
                                                        </select>
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
     					'enable_frost("'.$row['frost_temp'].'");',
     					'</script>'
					;
                                        echo '<script type="text/javascript">',
                                        'enable_frost_temp("'.$rowtype['id'].'");',
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

