<?php

// $artistSlug referres to the `slug` column of the `Artists` table

// after this function is complete, all new shows will have been
// added to the `Shows` table and the `Tracks` and `Venues` tables
// will have been updated appropriately.

	
function refresh_artist($artistSlug) {

	
	$dbhandle = database_connect();
	
	$query = "select name from artists where slug = '$artistSlug'";
	
	$result = mysqli_query($dbhandle, $query);
	
	if (mysqli_num_rows($result) == 0) {
		print("artist does not exist: ".$artistSlug."\n");
		
	}
	else {
		print("artist already exists\n");
	}
	
}

function refresh_show($archiveIdentifier) {

	$details_FRONT = 'https://archive.org/details/';
	$details_TAIL = '?output=json';
	
	$dbhandle = database_connect();
	$query = "select * from shows where archive_identifier = '$archiveIdentifier'"; 
	
	$result = mysqli_query($dbhandle, $query);
	
	$URL = $details_FRONT.$archiveIdentifier.$details_TAIL;
	$json = file_get_contents($URL);
	$infos = json_decode($json, true);
	
	$info = $infos['metadata'];

	$title = $info['title'][0];
	$date = $info['date'][0];
	$year = $info['year'][0];
	$source = $info['source'][0];
	$lineage = $info['lineage'][0];
	$transferer = $info['transferer'][0];
	$taper = $info ['taper'][0];
	$description = $info['description'][0];
		
	$reviews_count = intval($infos['reviews']['info']['num_reviews']);
	$avg_rating = intval($infos['reviews']['info']['avg_rating']);
	//$reviews = $infos['reviews']['reviews'];
			
	
	if (mysqli_num_rows($result) == 0) {
	
		print("data inserted\n");
		
		$query = "insert into shows (title, date, year, source, 
				  lineage, transferer, taper, description, archive_identifier, reviews, 
				  reviews_count, average_rating, duration, track_count, is_soundboard, weighted_avg,
				  createdAt, updatedAt, venueId, ArtistId) 
				  values ('".mysqli_real_escape_string($dbhandle, $title)."','$date', 
				  '$year', '".mysqli_real_escape_string($dbhandle, $source)."','$lineage','$transferer','$taper',
				  '".mysqli_real_escape_string($dbhandle, $description)."', '$archiveIdentifier', 0, '$reviews_count', 
				  '$avg_rating', 0, 0, 0, 0, 0, 0, 0, 0)";
	}
	
	else {
		print("data updated\n");
		$query = "update shows 
				  set title='".mysqli_real_escape_string($dbhandle, $title)."', 
				  date='$date', year='$year', source='".mysqli_real_escape_string($dbhandle, $source)."', 
				  lineage='$lineage', transferer='$transferer', taper='$taper', 
				  description='".mysqli_real_escape_string($dbhandle, $description)."', 
				  archive_identifier='$archiveIdentifier', reviews=0, 
				  reviews_count='$reviews_count', average_rating='$avg_rating', duration=0, 
				  track_count=0, is_soundboard=0, weighted_avg=0,
				  createdAt=0, updatedAt=0, venueId=0, ArtistId=0 
				  where archive_identifier = '$archiveIdentifier'";
		
	}
		
	$result = mysqli_query($dbhandle,$query);
	if (!$result) {
		print(mysqli_error($dbhandle)."\n");
	}
	//venues, tracks, shows 
}

//update shows for each artist
function update_shows_by_artist() {

	$collection_FRONT = "http://archive.org/advancedsearch.php?q=collection%3A";
	$collection_TAIL = "&fl%5B%5D=date&fl%5B%5D=identifier&fl%5B%5D=year&sort%5B%5D=year+asc&sort%5B%5D=&sort%5B%5D=&rows=9999999&page=1&output=json&save=yes";
	
	$dbhandle = database_connect();
	$query="select archive_collection from artists";
	$result = mysqli_query($dbhandle, $query);
	
	while ($row=mysqli_fetch_array($result)) {
		
		$URL = $collection_FRONT.$row[0].$collection_TAIL;
		
		$json = file_get_contents($URL);
		$infos = json_decode($json, true);
		$info = $infos["response"]["docs"];
		
		foreach ($info as $show) {
			
			$ai = $show["identifier"];
			
			refresh_show($ai);
		}
	}
}

//database connect
function database_connect(){
		$dbhandle = mysqli_connect("127.0.0.1","root","snowman42","lotusod");
		if (mysqli_connect_errno())
		{
  			echo "MySQL Connection Failure: " . mysqli_connect_error();
  		}
  		return $dbhandle; 
}

//clear shows table
function clearshows () {
	
	$dbhandle = database_connect();
	
	$query = "truncate table shows"; 
	
	$result = mysqli_query($dbhandle, $query);
}


//check if it is artist or show

if ($argv[1] == 'artist') {
	
	refresh_artist($argv[2]);
	
}

else if ($argv[1] == 'show') {
	
	$stdin = fopen('php://stdin', 'r');
	
	while (1) {
		
		update_shows_by_artist();
		print("enter exit to stop the program: \n");
		
		$read = array($stdin);
		$write = array();
		$except = array();

		
		if (stream_select($read, $write, $except, 2)) {
			$input = fgets($stdin);
			
			if ($input == 'exit'.PHP_EOL) {
				fclose($stdin);
				exit();
			}
		}
		
		sleep(1);
	}
}

else if ($argv[1] == 'clear') {
	
	clearshows ();	
}

else {
	print("Please re-enter the type of info you want to inquiry\n");
}




?>