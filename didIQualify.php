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
    // Set up style for colouring qualified and alternating grey/white
    echo "<head>
            <style>
            table {
              width:windowSize%;
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

    $server = $_SERVER['HTTP_HOST'];

    $liveResultsUrl = "https://liveresultat.orientering.se/api.php";
    $debug = $_GET['debug'];
    if ( $debug == "true" ) {
        echo "Debugging output turned on. </br></br>";
    }

    $eventId = $_GET['eventId'];
    if ( $eventId == "" ) {
        //Get a list of events and provide urls for live results
        
        //First get the competitions list
        $getCompsUrl = $liveResultsUrl . "?method=getcompetitions";
        
        if ( $debug == "true" ) {
            echo $getCompsUrl, "</br>";
        }
        
        $comps = file_get_contents($getCompsUrl);
        $comps = json_decode($comps, true);
        
        //var_dump( $comps['competitions'] );
        $compArr = $comps['competitions'];
    //    print_r($compArr);

        echo "<table><tr><th>Date</th><th>Competition</th><th>Organiser</th></tr>";
        foreach( $compArr as $comp ) {
            //if ( stripos( $comp['name'], "WOC" ) !== false && stripos( $comp['name'], "qual" ) ) {
            if ( stripos( $comp['name'], "qual" ) ) {
                echo "<tr><td>", $comp['date'], "</td><td> <a href=\"http://", $server, "/didIQualify.php?eventId=", $comp['id'], "\">", $comp['name'], "</a></td><td> ", $comp['organizer'], "</td></tr>";
            }
            //$classComps['class'] = $sex . " A";
            //array_push( $classResults, $classComps );
        }
        echo "</table></br>";
        exit();
    }
    
    $eventId = $_GET['eventId'];
    //First get the competitions info
    $getCompInfo = $liveResultsUrl . "?method=getcompetitioninfo&comp=" . $eventId;
    
    if ( $debug == "true" ) {
        echo "getCompInfo url: ", $getCompInfo, "</br>";
    }
    
    $compInfo = file_get_contents($getCompInfo);
    $compInfo = json_decode($compInfo, true);

    echo $compInfo['date'], " ", $compInfo['name'], "</br>";

    //FIXME remove this hard code
//    $eventId = "14154";

    echo "</br>Refresh manually to update results</br>";
    echo "<a href=\"https://liveresultat.orientering.se/followfull.php?comp=", $eventId, "&lang=en\">IOF Live Results</a></br>";
    
    //TODO Check for 3 classes of men and women that match the expected qualification heats
    // Actually don't really need to test that, can just assume first 3 classes are one grouping and the second 3 are another
    $getClasses = $liveResultsUrl . "?method=getclasses&comp=" . $eventId;
    
    if ( $debug == "true" ) {
        echo "getClasses url: ", $getClasses, "</br>";
    }
    
    $compClasses = file_get_contents($getClasses);
    $compClasses = json_decode($compClasses, true);

    // Double check for 6 classes
    if ( sizeof($compClasses['classes']) !== 6 ) {
        echo "This webpage is designed for only qualification competitions with 6 classes, 3 men and 3 women. It may not work as expected for this competition</br></br>";
    }

    // Print the links for each class. First set up font size
    echo "<p style=\"font-size:35px\">";
    // Then loop over classes to create the urls
    $iter = 0;
    foreach( $compClasses['classes'] as $class ) {
        //echo $class['className'], "</br>";
        echo "<a href=\"http://", $server, "/didIQualify.php?eventId=", $eventId, "&class=", $class['className'], "\">", $class['className'], "</a>", "\n\n\n\n\n";
        $iter = $iter + 1;
        if ( $iter == 3 ) {
            echo "<a href=\"http://", $server, "/didIQualify.php?eventId=", $eventId, "&class=MEN ALL\">MEN ALL</a>", "</br>";
        } elseif ( $iter == 6 ) {
            echo "<a href=\"http://", $server, "/didIQualify.php?eventId=", $eventId, "&class=WOMEN ALL\">WOMEN ALL</a>", "</br>";
        }
    }
    echo "</p>";

    $className = $_GET['class'];

    if ( $className == "" ) {
        exit();
    }

    $iter = 0;
    $init = 0;
    $end = 0;
    foreach( $compClasses['classes'] as $class ) {
        if ( $class['className'] == $className && $iter < 3 ) {
            $init = 0;
            $end = 3;
            $classCase = 1;
            break;
        } elseif ( $class['className'] == $className && $iter < 6 ) {
            $init = 3;
            $end = 6;
            $classCase = 2;
            break;
        } elseif ( $className == "MEN ALL" ) {
            $init = 0;
            $end = 3;
            $classCase = 3;
            break;
        } elseif ( $className == "WOMEN ALL" ) {
            $init = 3;
            $end = 6;
            $classCase = 4;
            break;
        }
        $iter += 1;
    }

    if ( $debug == "true" ) {
        echo "classCase: ", $classCase, "</br>";
    }

    $liveResultsUrl = "https://liveresultat.orientering.se/api.php";
    
    $classResults = array();

    //Class Results downloaded look like:
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
         "progress":windowSize,
         "start":6840000
      },

    However, we only want the "results" part so strip that and add a class key to it
    */

    for ( $i = $init; $i < $end; $i++ ) {
        $getClass = $compClasses['classes'][$i]['className'];
        $getClass_noSpace = str_replace( " ", "%20", $getClass );
        if ( $debug == "true" ) {
            echo "getClass: ", $getClass, "</br>";
        }
        $getResultsURL = $liveResultsUrl . "?comp=" . $eventId . "&method=getclassresults&unformattedtimes=true&class=" . $getClass_noSpace;
        if ( $debug == "true" ) {
            echo "getResultsURL: ", $getResultsURL, "</br>";
        }
        $classResultsLive = file_get_contents($getResultsURL);
        $classResultsLive = json_decode( $classResultsLive, true );
        foreach( $classResultsLive['results'] as $classComps ) {
            $classComps['class'] = $getClass;
//            print_r( $classComps );
            array_push( $classResults, $classComps );
        }
    }

// commented out printing
/*
    echo "new method</br>";
    print_r( $classResults );
    echo "</br></br></br>";
*/

    
    usort($classResults, 'compare_place_and_time');
    //echo "</br></br></br>Post sort</br>";
    //print_r($classResults);
    //echo "</br></br></br>";
    
    //print_r($classResultsArr);
    $finalComps = array();
    $finalClubs = array();
    

    $printAll = false;
    if ( $className == "MEN ALL" or $className == "WOMEN ALL") {
            $printAll = true;
            echo "List of finished  runners in all heats, green means provisionally qualified! Refresh your page to update results", "</br>";
            echo "<table style=\"width:windowSize%\"><tr> <th>Place</th><th>Class</th><th>Name</th><th>Country</th><th>Time</th><th>Time Behind</th></tr>";
    }
    
    // Check who should be qualified
    foreach ( $classResults as &$result ){
        // Everyone in positions 1-15 are automatically in
        if ( $result['place'] != '-' and $result['place'] != ' ' ) {
            if( $result['place'] <= 15 ) {
                //echo "<tr><td>", $result['place'], "</td><td>", $result['class'], "</td><td>", $result['name'], "</td><td>", $result['club'], "</td><td>", $result['result'], "</td><td>", $result['timeplus'], "</td></tr>";
                // Add the result to the list and add the "club" (i.e. country) to a list
                array_push( $finalComps, $result );
                array_push( $finalClubs, $result['club']);
                $result['qual'] = true;
            }
            // TODO check for time behind is not > 100%
            // Max final size is 60 and we just fill up to that size
            elseif ( sizeof($finalClubs) < 60 ){
                // If no one from this club is already in then add this result
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
        // In the ALL mode just print everyone in order
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
    
    // Otherwise loop over the competitors and print the ones from the chosen heat
    if ( !$printAll ) {
        echo "List of finished  runners in heat, green means provisionally qualified! Refresh your page to update results", "</br>";
        echo "<table style=\"width:windowSize%\"><tr> <th>Place</th><th>Class</th><th>Name</th><th>Country</th><th>Time</th><th>Time Behind</th></tr>";
        
        foreach ( $classResults as $result ) {
            //print_r( $result );
            //echo "</br>";
            if ( $result['class'] == $className ) {
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
        echo "<a href=\"http://", $server, "/didIQualify.php/test.php\">", $comp['name'], "</a></br>";
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
