<?php
    /* Take live results from 
    
    Rules summaries are as follows:
    Middle:
    - First 15 in each heat + ties
    - First person from each country that doesn't already have someone qualified to make up to 60
    - If two athletes from the same country have the same place in different heats, the one who is the least amount of time behind the winner of their heat is chosen
    
    For full rules see:
    https://orienteering.sport/how-to-qualify-for-woc-2019-long-distance/
    
    */

/*table, th, td {
              border: 1px solid black;
              border-collapse: collapse;
            }*/
    echo "<head>
            <style>
            table {
              width:100%;
            }
            th, td {
              padding: 15px;
              text-align: left;
            }
            tr:nth-child(even) {
              background-color: #eee;
            }
            tr:nth-child(odd) {
             background-color: #fff;
            }
            th {
              background-color: black;
              color: white;
            }
            </style>
        </head>";

    //TODO: make a type your own name in version
    //      take event id as argument?
    //      
    
    // Sort by position and then time behind. This ensures ties in placings are sorted by time behind, thus the person with the lowest time behind is selected first
    function compare_place_and_time($a, $b)
      {
        //Need to account for position "-" which is DNF or not yet finished in some way
        if ( $a['place'] == "-" ) { return true; }
        elseif ( $b['place'] == "-" ) { return false; }
        
        $retval = strnatcmp($a['place'], $b['place']);
        // if placing is identical, sort by first name
        if(!$retval) $retval = strnatcmp($a['timeplus'], $b['timeplus']);
        return $retval;
        
      }

    $class = $_GET['class'];
    
    $compId = "14154";
    echo "<a href=\"https://liveresultat.orientering.se/followfull.php?comp=", $compId, "&lang=en\">IOF Live Results</a></br>";
    
    echo "<p style=\"font-size:35px\">",
         "<a href=\"http://cnocmaps.com/didIQualify.php?class=MEN A\">MEN A</a>", "\n\n\n\n\n", "<a href=\"http://cnocmaps.com/didIQualify.php?class=MEN B\">MEN B</a>", "\n\n\n\n\n",
         "<a href=\"http://cnocmaps.com/didIQualify.php?class=MEN C\">MEN C</a>", "\n\n\n\n\n", "<a href=\"http://cnocmaps.com/didIQualify.php?class=MEN ALL\">MEN ALL</a>", "</br>",
         "<a href=\"http://cnocmaps.com/didIQualify.php?class=WOMEN A\">WOMEN A</a>", "\n\n\n\n\n", "<a href=\"http://cnocmaps.com/didIQualify.php?class=WOMEN B\">WOMEN B</a>", "\n\n\n\n\n",
         "<a href=\"http://cnocmaps.com/didIQualify.php?class=WOMEN C\">WOMEN C</a>", "\n\n\n\n\n", "<a href=\"http://cnocmaps.com/didIQualify.php?class=WOMEN ALL\">WOMEN ALL</a>",
         "</p>";
    
    if ( $class == "MEN A"  or $class == "MEN B" or $class == "MEN C" or $class == "MEN ALL") {
        $sex = "MEN";
    } elseif ( $class == "WOMEN A" or $class == "WOMEN B" or $class == "WOMEN C" or $class == "WOMEN ALL") {
        $sex = "WOMEN";
    } else {
        exit();
    }

    $liveResultsUrl = "https://liveresultat.orientering.se/api.php";
    
    $classResults = array();
    
    $getClass = $sex . "%20A";
    $getResultsURL = $liveResultsUrl . "?comp=" . $compId . "&method=getclassresults&unformattedtimes=true&class=" . $getClass;
    //echo $getResultsURL, "</br>";
    $classResultsA = file_get_contents($getResultsURL);
    $classResultsA = json_decode( $classResultsA, true );
    foreach( $classResultsA['results'] as $classComps ) {
        $classComps['class'] = $sex . " A";
        array_push( $classResults, $classComps );
    }
    
    
    $getClass = $sex . "%20B";
    $getResultsURL = $liveResultsUrl . "?comp=" . $compId . "&method=getclassresults&unformattedtimes=true&class=" . $getClass;
    //echo $getResultsURL, "</br>";
    $classResultsB = file_get_contents($getResultsURL);
    $classResultsB = json_decode( $classResultsB, true );
    foreach( $classResultsB['results'] as $classComps ) {
        $classComps['class'] = $sex . " B";
        array_push( $classResults, $classComps );
    }

    
    $getClass = $sex . "%20C";
    $getResultsURL = $liveResultsUrl . "?comp=" . $compId . "&method=getclassresults&unformattedtimes=true&class=" . $getClass;
    //echo $getResultsURL, "</br>";
    $classResultsC = file_get_contents($getResultsURL);
    $classResultsC = json_decode( $classResultsC, true );
    foreach( $classResultsC['results'] as $classComps ) {
        $classComps['class'] = $sex. " C";
        array_push( $classResults, $classComps );
    }
    
   // print_r($classResults);
    
    /*$classResultsArr = array(
        "MEN A" => $classResultsA,
        "MEN B" => $classResultsB,
        "MEN C" => $classResultsC,
    );*/
        
        
    
    //Class Results look like:
    /*
    {
   "status":"OK",
   "className":"Gul h",
   "splitcontrols":[

   ],
   "results":[
      {
         "place":"1",
         "name":"Anton Mörkfors",
         "club":"Järfälla OK",
         "result":"17:02",
         "status":0,
         "timeplus":"+00:00",
         "progress":100,
         "start":6840000
      },
    */
    
    //$classes = ["MEN%20A", "MEN%20B", "MEN%20C"];
    
    //echo "</br></br></br>";
    usort($classResults, 'compare_place_and_time');
    //print_r($classResults);
    //echo "</br></br></br>";
    
    //print_r($classResultsArr);
    $finalComps = array();
    $finalClubs = array();
    

    $printAll = false;
    if ( $class == "MEN ALL" or $class == "WOMEN ALL") {
            $printAll = true;
            echo "List of finished  runners in all heats, green means provisionally qualified! Refresh your page to update results", "</br>";
            echo "<table style=\"width:100%\"><tr> <th>Place</th><th>Class</th><th>Name</th><th>Country</th><th>Time</th><th>Time Behind</th></tr>";
    }
    
    foreach ( $classResults as &$result ){
        if ( $result['place'] != '-' ) {
            if( $result['place'] <= 15 ) {
                //echo "<tr><td>", $result['place'], "</td><td>", $result['class'], "</td><td>", $result['name'], "</td><td>", $result['club'], "</td><td>", $result['result'], "</td><td>", $result['timeplus'], "</td></tr>";
                array_push( $finalComps, $result );
                array_push( $finalClubs, $result['club']);
                $result['qual'] = true;
            }
            elseif ( sizeof($finalClubs) < 60){
                if( !in_array( $result['club'], $finalClubs ) ){
                   // echo "<tr><td>", $result['place'], "</td><td>", $result['class'], "</td><td>", $result['name'], "</td><td>", $result['club'], "</td><td>", $result['result'], "</td><td>", $result['timeplus'], "</td></tr>";
                    array_push( $finalComps, $result );
                    array_push( $finalClubs, $result['club']);
                    $result['qual'] = true;
                }
            }
            else {
                $result['qual'] = false;
            }
            
        }
        if ( $printAll ) {
            if ( $result['qual'] == "1" ) {
                    echo "<tr style=\"background-color: #90EE90;\">";
                } else {
                    echo "<tr>";
                }
            echo "<td>", $result['place'], "</td><td>", $result['class'], "</td><td>", $result['name'], "</td><td>", $result['club'], "</td><td>", $result['result'], "</td><td>", $result['timeplus'], "</td></tr>";
        }
    }

    
    //echo "</br></br></br>";
    
    if ( !$printAll ) {
        echo "List of finished  runners in heat, green means provisionally qualified! Refresh your page to update results", "</br>";
        echo "<table style=\"width:100%\"><tr> <th>Place</th><th>Class</th><th>Name</th><th>Country</th><th>Time</th><th>Time Behind</th></tr>";
        
        foreach ( $classResults as $result ) {
            //print_r( $result );
            //echo "</br>";
            if ( $result['class'] == $class ) {
                if ( $result['qual'] == "1" ){
                    echo "<tr style=\"background-color: #90EE90;\">";
                } else {
                    echo "<tr>";
                }
                echo "<td>", $result['place'], "</td><td>", $result['class'], "</td><td>", $result['name'], "</td><td>", $result['club'], "</td><td>", $result['result'], "</td><td>", $result['timeplus'], "</td></tr>";
            }
        }
    }
        
    echo "</table>";
    
   /* foreach ( $classResultsArr as $class ){
        $classResults = $class['results'];
        foreach($classResults as $result) {
            if ( $result['place'] != '-' ) {
               
            }
        }
    }*/
    
    /*echo "</br></br></br>";
    echo "List of qualified countries in order: ", "</br>";
    foreach($finalClubs as $country) {
        echo $country, "</br>";
    }*/
    
    /*// Loop over all the results and print the placing, name & time
    foreach($classResults['results'] as $result) {
        echo $result['place'], "\n", $result['name'], "\n", $results['club'], "\n", $result['result'], "\n", $result['timeplus'], "</br>";
    }
    */
    //Sample code for how to get the list of competitions
    /*
    //First get the competitions list
    $getCompsUrl = $liveResultsUrl . "?method=getcompetitions";
    
    echo $getCompsUrl, "</br>";
    
    $comps = file_get_contents($getCompsUrl);
    $comps = json_decode($comps, true);
    
    //var_dump( $comps['competitions'] );
    $compArr = $comps['competitions'];
    //print_r($compArr);
    
    foreach( $compArr as $comp ) {
        echo "<a href=\"http://cnocmaps.com/didIQualify.php/test.php\">", $comp['name'], "</a></br>";
    }
    */
    
    // Curl doesn't seem to get the nested parts of the array for some reason
    /* 
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://liveresultat.orientering.se/api.php?comp=14154&method=getclassresults&unformattedtimes=true&class=MEN A",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache"
    ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);/*
    
    $output = file_get_contents("https://liveresultat.orientering.se/api.php?comp=14154&method=getclassresults&unformattedtimes=true&class=MEN%20A");
    
    $output = json_decode($output, true);
    
    print_r( $output['results'][0]['name']);
    
    //print_r( $json['results'][0]['name'] );
    //echo "\n"
    
    //$response = json_decode($response, true); //because of true, it's in an array
    //var_dump($response)
    //print_r($response)
    


   // print_r($response['results'])
    
   // echo $response['status']. "\n"; //['name'];
    //$results = $response['results'][0];
    
   // echo $results. "\n";
    
    //print_r($results)
    
    //foreach ($results as $val){
    //    echo "1" //$val['name']. "\n"
    //}
    //echo 'Online: '. $response['players']['online'];

    echo

?>
