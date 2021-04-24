<?php
    // initialize variables
    include 'utils.php';
    $password = 'password';
    
    $score_file = 'data/scores.json';
    $config_file = 'data/config.json';
    $trading_file = 'data/trading_rates.json';
    // read the scores, tasks configurations and trading rates
    $data = json_decode(file_get_contents($score_file), true);
    $config = json_decode(file_get_contents($config_file), true);
    $rates = json_decode(file_get_contents($trading_file), true);
    $tasks = get_tasks($config_file);
    $nbtasks = count($tasks);
    $users = get_users($score_file);
    // when submit is clicked
    if(isset($_POST["submit"])){
        // check if there is a password and it is the good one (as a confirmation process)
        if(check_password($_POST["password"], $password)){
            // if a new task is being defined
            if(strcmp($_POST["new_task"],'') != 0){
                $task = $_POST["new_task"];
                foreach($data as $mate => &$val){ // create a new score for each roommate with zero points
                    $val[$task] = 0;
                }
                $config[$task] = array('default' => 1); // also create a new configuration with one 'default' type
                // create the trading rates matrix
                if(count($rates) == 0){ // if empty, create a 1x1 matrix with just the task
                    $rates[$task] = array($task => 1);
                }
                else{ // if not empty, expend the matrix to NxN where N is the new count of taskss
                    foreach($rates as $from => &$to){
                        $to[$task] = 1; // rate of one by default
                    }
                    $rates[$task] = end($rates);
                }
                // write the data and confirm the creation
                file_put_contents($score_file, json_encode($data));
                file_put_contents($config_file, json_encode($config, JSON_FORCE_OBJECT));
                file_put_contents($trading_file, json_encode($rates));
                echo "New task ".$task." added for all users ! <br>";
            }
            // if a new roommate is being defined
            if(strcmp($_POST["new_name"],'') != 0){
                $user = $_POST["new_name"];
                $data[$user] = $data["default"]; // just modify the scores with the 'default' template
                file_put_contents($score_file, json_encode($data, JSON_FORCE_OBJECT));
                echo "New user ".$user." added ! <br>";
            }
            echo "</center> </div>";
        }
    }
    // show data/configuration mode
    if(isset($_POST["reset_one"])){
        $class = 'visible';
    }
    else{
        $class = 'hidden';
    }
    // to reset all data
    if(isset($_POST['reset_two']) && check_password($_POST["password"], $password)){
        $reset_config = array();
        // create the 'default' template for the scores
        $reset_scores = array('default' => array());
        $reset_trading_rates = array();
        // write the *empty* data
        file_put_contents($config_file, json_encode($reset_config, JSON_FORCE_OBJECT));
        file_put_contents($score_file, json_encode($reset_scores, JSON_FORCE_OBJECT));
        file_put_contents($trading_file, json_encode($reset_trading_rates, JSON_FORCE_OBJECT));
    }

    // configuration actions

    // modify existing user
    if(isset($_POST['submit_modify_user'])){
        if(isset($_POST['password']) && check_password($_POST["password"], $password)){
            $modify = $_POST['user_to_modify'];
            $name = $_POST['user_new_name'];
            // modify in the score database
            $score = $data[$modify];
            unset($data[$modify]);
            $data[$name] = $score;
            file_put_contents($score_file, json_encode($data, JSON_FORCE_OBJECT));
            echo "<div id='pane'> User modified </center> </div>";
        }
    }
    // remove user
    if(isset($_POST['submit_remove_user'])){
        if(isset($_POST['password']) && check_password($_POST["password"], $password)){
            $remove = $_POST['user_to_remove'];
            // modify in the score database
            unset($data[$remove]);
            file_put_contents($score_file, json_encode($data, JSON_FORCE_OBJECT));
            echo "<div id='pane'> User removed </center> </div>";
        }
    }

    // modify existing task
    if(isset($_POST['submit_modify_task'])){
        if(isset($_POST['password']) && check_password($_POST["password"], $password)){
            $modify = $_POST['task_to_modify'];
            $name = $_POST['task_new_name'];
            // modify in the scores database
            foreach($data as &$users_scores){ // for each user
                $score = $users_scores[$modify]; // grab score of the modified task
                unset($users_scores[$modify]); // remove the task
                $users_scores[$name] = $score; // create a new task with the corresponding score
            }
            // modify in the configuration database
            $task_config = $config[$modify];
            unset($config[$modify]);
            $config[$name] = $task_config;
            // modify in the trading rates database: erase corresponding row and column
            $previous_rates = $rates;
            unset($rates[$modify]);
            foreach($rates as &$row){
                unset($row[$modify]);
            }
            // now expend the matrix
            foreach($rates as $task_r => &$task_c_vals){
                $task_c_vals[$name] = 1;
            }
            $rates[$name] = end($rates);
            // put the right trades
            foreach($rates as $from => &$to){
                foreach($to as $task => &$val){
                    if($from == $name || $task == $name){
                        if($from == $name){
                            $from = $modify;
                        }
                        if($task == $name){
                            $task = $modify;
                        }
                    }
                    $val = $previous_rates[$from][$task];
                }
            }
            echo "<div id='pane'> Task modified </center> </div>";
            file_put_contents($config_file, json_encode($config, JSON_FORCE_OBJECT));
            file_put_contents($score_file, json_encode($data, JSON_FORCE_OBJECT));
            file_put_contents($trading_file, json_encode($rates, JSON_FORCE_OBJECT));
        }
    }
    // remove task
    if(isset($_POST['submit_remove_task'])){
        if(isset($_POST['password']) && check_password($_POST["password"], $password)){
            $name = $_POST['task_to_remove'];
            // remove from the scores database
            foreach($data as &$users_scores){ // for each user
                unset($users_scores[$name]); // remove the task
            }
            // remove from the configuration database
            $task_config = $config[$name];
            unset($config[$name]);
            // remove from the trading rates database: erase corresponding row and column
            unset($rates[$name]);
            foreach($rates as &$row){
                unset($row[$name]);
            }
            var_dump($config);
            var_dump($data);
            var_dump($rates);
            die();
            echo "<div id='pane'> Task removed </center> </div>";
            file_put_contents($config_file, json_encode($config, JSON_FORCE_OBJECT));
            file_put_contents($score_file, json_encode($data, JSON_FORCE_OBJECT));
            file_put_contents($trading_file, json_encode($rates, JSON_FORCE_OBJECT));
        }
    }
?>

<!DOCTYPE html>
<html>

<head>
    <title>&#x1F6BF Admin page </title>
    <!-- <link rel="stylesheet" type="text/css" href="/artos/style.css" media="screen"/> -->
    <link rel="stylesheet" type="text/css" href="style.css" media="screen"/>
</head>

<body>
    <div id="pane">
        <header>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <h1> <a href = "" > Admin page </a> </h1>
        </header>

        <form method='POST' action='admin.php'>
            <p> <h3> Admin password </h3> :<input type='text' name='password' /></p>
            <p> Add a new roommate :<input type='text' name='new_name' /></p>
            <p> Add a new task :<input type='text' name='new_task' /></p>
            <p><input class='button' type='submit' name='submit' value='Add' /></p> 

        <?php
            if(count($users)>0){
                // modify existing types if any
                echo "<label for='user_to_modify'> Modify a user :</label>";
                echo "<select name='user_to_modify' id='user_to_modify'>";
                // make each type selectable
                foreach($users as $user){
                    echo "<option value='".$user."'> ".$user."</option>";
                }
                echo "</select>";
                echo "<p> New name <input type='text' name='user_new_name' /></p>";
                echo "<p><input type='submit' name='submit_modify_user' value='Modify user' /></p>";
                // remove type
                echo "<form method='POST' action='admin.php'>";
                echo "<label for='user_to_remove'> Remove a user :</label>";
                echo "<select name='user_to_remove' id='user_to_remove'>";
                // make each type selectable
                foreach($users as $user){
                    echo "<option value='".$user."'> ".$user." </option>";
                }
                echo "</select>";
                echo "<p><input type='submit' name='submit_remove_user' value='Remove user' /></p>";
            }
            if($nbtasks>0){
                // modify existing types if any
                echo "<form method='POST' action='admin.php'>";
                echo "<label for='task_to_modify'> Modify a task :</label>";
                echo "<select name='task_to_modify' id='task_to_modify'>";
                // make each type selectable
                foreach($tasks as $task){
                    echo "<option value='".$task."'> ".$task."</option>";
                }
                echo "</select>";
                echo "<p> New name <input type='text' name='task_new_name' /></p>";
                echo "<p><input type='submit' name='submit_modify_task' value='Modify task' /></p>";
                // remove type
                echo "<form method='POST' action='admin.php'>";
                echo "<label for='task_to_remove'> Remove a task :</label>";
                echo "<select name='task_to_remove' id='task_to_remove'>";
                // make each type selectable
                foreach($tasks as $task){
                    echo "<option value='".$task."'> ".$task." </option>";
                }
                echo "</select>";
                echo "<p><input type='submit' name='submit_remove_task' value='Remove task' /></p>";
            }
        ?>

            <input class='button' type='submit' name='reset_one' value='&#9888; Reset all &#9888;'/>
        </form>
        <?php
            // display configuration mode if needed
            if($class == 'visible'){
                echo "<p> <input class='button' type='submit' name='reset_two' value='Sure ?' /> </p>";
            }
        ?>
        </form>
        <footer>
            <nav>
                <ul>
                    <li><a href="index.php"> Return to homepage </a></li>
                </ul>
            </nav>
        </footer>
        
    </div>
</body>
</html>

<?php
    // in the end, always update the scores data given the tasks newly created
    update_tasks_in_scores($config_file, $score_file);
?>