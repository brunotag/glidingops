<?php
$org=0;
if ($_SERVER["REQUEST_METHOD"] == "GET")
{
 if (isset($_GET['org']) )
 {
   $org=$_GET['org'];
 }else{
  die("Error You must supply and organisation number");
 }
}
?>
<?php $inc = "./orgs/" . $org . "/camera/cameras.php"; include $inc; ?>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Papawai Webcams</title>
  <style>
    body {font-family: Arial, Helvetica, sans-serif;font-size: 12pt;color:LIGHTGRAY; margin: 0;background-color: #303050;}

    div.d1 {text-align: center;padding: 10px;}
    @media screen and (max-width: 450px)
    {
        h1 {color: Blue;text-align: center;font-size: 12pt}
	p {font-size: 9pt}
    }
    @media screen and (max-width: 1200px) and (min-width: 451px)
    {
        h1 {color: Gold;text-align: center; font-size: 15pt;}
	p {font-size: 9pt}
    }
    @media screen and (min-width: 1201px)
    {
        h1 {color: Gold;text-align: center;}
    } 

    h1,p {
      margin:5px;
    }
    .imagecontainer img {width: 80%; height: auto; }


    .slider-container {
      max-width: 80%; /* Set the maximum width of the container to 95% of the viewport width */
      margin: 0 auto; /* Center the container horizontally */
    }
    
    .image {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
	
    .slider {
      -webkit-appearance: none;
      width: 100%;
      height: 15px;
      background: #5050A0;
      outline: none;
      opacity: 0.7;
      -webkit-transition: .2s;
      transition: opacity .2s;
    }

    .slider:hover {
      opacity: 1;
    }


    /* Customize the appearance of the slider knob */
    input[type="range"]::-webkit-slider-thumb {
      -webkit-appearance: none;
      appearance: none;
      width: 65px;
      height: 65px;
      background: url('/img/slider.png') center center no-repeat;
      background-size: cover;
      border-radius: 50%;
      cursor: pointer;
    }

    .slider::-moz-range-thumb {
      width: 25px;
      height: 25px;
      background: ORANGE;
      cursor: pointer;
    }
    
  </style>

  <script> 
    function loadImageContainer(container, fileList, images) {
      fileList.forEach((fileName, index) => {
        const image = new Image();
        image.src = `${fileName}`;
        image.alt = `Image ${fileName}`;

        // Add an error event listener
        image.addEventListener('error', () => { 	// If an error occurs, use the source of the previous image
          image.src = images[index - 1].src;
        });
        container.appendChild(image);
      });
      for (const img of images) {img.style.display = 'none'; } // Hide all images
      images[images.length - 1].style.display = 'block';
    }

    function wireSliderToImages(sliderx,images,event) {
      var slider = document.getElementById(sliderx);
      slider.addEventListener('input', function (event) {
        var sliderValue = event.target.value;
        const visibleImage = images[slider.value];
        for (const img of images) {  img.style.display = 'none'; }  // Hide all images
        if (visibleImage) {   visibleImage.style.display = 'block'; } // Show the current image
      });
    };
  </script>
</head>
<body>
<center>
<h1>Papawai Webcams</h1>
<p id="thoughtOfTheDay"></p>
<?php
  foreach ($camera_names as $camera_name) {
    $file_prefix = $camera_name . "-";

    $dir_path = dirname(__FILE__) . "/orgs//" . $org . "/camera//";
    if (is_dir($dir_path)) {
      $file_names = scandir($dir_path);  
?>
    <div class="imagecontainer" id="<?php echo "imagecontainer".$camera_name; ?>"></div>
    <script>
      var container = document.getElementById('<?php echo "imagecontainer".$camera_name; ?>'); 
      var images = container.getElementsByTagName('img');  
      var fileList = [];
<?php
      $img_files_count = 0;
      foreach ($file_names as $file_name) {
        $file_path = $dir_path . '/' . $file_name;
        if (is_file($file_path)) {
          $file_name_starts_with_prefix = (substr($file_name, 0, strlen($file_prefix)) === $file_prefix);
          if ($file_name_starts_with_prefix) {
		    $img_files_count++;
?>
    fileList.push(['<?php echo "./orgs/" . $org . "/camera//" . $file_name; ?>']);
<?php
          }        
        }
      }
    }
?>

  if (fileList.length == 0){
    fileList.push(['/img/noimage.jpg'])
  }
  loadImageContainer(container,fileList,images);
	</script>	  
    <div class="slider-container">
      <input type="range" 
        id="<?php echo "slider".$camera_name; ?>" 
        min="0" 
        max="<?php echo $img_files_count-1; ?>" 
        value="<?php echo $img_files_count-1; ?>" 	
        class="slider"
      ><p><p>
    </div>
	<script>
      document.addEventListener('DOMContentLoaded', wireSliderToImages('<?php echo "slider".$camera_name; ?>',images,event));           
    </script>	
    <p><p><hr>	
<?php
  }
?>
<script> 
    function reloadPageIfMinuteIsTwo() { // Set up a function to reload the page 2 min after each 5 min interval
      function checkAndReload() {
        const currentDate = new Date();
        const minute = currentDate.getMinutes();
        if (minute % 5 === 2) {location.reload(true); 
        }
      }
      setInterval(checkAndReload, 60000); // 60000 milliseconds = 1 minute
    }
  
</script>
<script>
    const gliderSayings = [
      "Soar high, stay humble.",
      "Wings of patience catch the strongest thermals.",
      "Glide gracefully, navigate wisely.",
      "Better to be on the ground wishing you were in the air, than in the air wishing you were on the ground",
      "Eagles may soar, but weasels don't get sucked into jet engines.",
      "Altitude is earned, not given.",
      "In the cockpit of life, adjust your attitude for the smoothest ride.",
      "Life is a journey, and sometimes you need to climb through turbulence to ride the wave.",
      "A good pilot is always learning, on the ground and in the air.",
      "Thermals may lift you, but skill keeps you soaring.",
      "See the horizon as a challenge, not a limit.",
      "The best views come after the hardest climbs.",
      "I'M SAFE: Illness, Medication, Stress,  Alcohol, Fatigue, Eating",
      "Pre-boarding - ABCDE: Airworthy, Ballast, Controls, Dollies, Expectations",
      "Pre-Takeoff - CCB SIFT BEC: Controls, Ballast - Straps, Instruments, Flaps, Trim - Brakes, Eventualities, Canopy (Weak Link!)",
      "Winch launch - WASOB: Wing - Attitude - Speed - Overspeed - Break" ,
      "Aerotow launch - SASOB: Straight - Accelerate - Signals - Out of Position - Break",
      "Pre-aerobatic - HASELL: Height, Airframe, Security, Engine, Locality, Lookout",
      "Pre-Landing - SUFB: Straps, Undercarriage, Flaps, Brakes",
      "Beep . . . . . beep . . . beep . . beep beep BeepBeepBeepBeep!!!",
      "Wairarapa traffic, glider golf papa juliet is three to the north-west of Greytown, two thousand two hundred feet, circling and climbing at 6kts",
      "Papawai traffic, glider golf romeo joining down wind for 21 right hand",
      "All clear above and behind. Take up slack.",
      "Glider Golf Romeo switching to 133.55",
    ];

    // Function to display a random saying
    window.onload=function displayRandomSaying() {
      const randomIndex = Math.floor(Math.random() * gliderSayings.length);
      const randomSaying = gliderSayings[randomIndex];
      document.getElementById("thoughtOfTheDay").textContent = randomSaying;
    }

    // Call the function on page load
    reloadPageIfMinuteIsTwo();

</script>

<p>[V 2.51 Under construction - your mileage may vary.]</p>
</body>
</html>


