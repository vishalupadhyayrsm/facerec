<!doctype html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.2.0/dist/tf.min.js"> </script>
  <script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2@1.2.6/dist/quagga.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-800 ">
    <div class="w-full h-auto  mb-10 shadow-xl py-2">
        <h2 class="text-center font-serif text-4xl font-black text-white">Scanner</h2>
    </div>
    <!-- this is video box container -->
    <div class="grid sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 lg:mx-20">
        <div class="w-10/12 h-auto mx-14 space-y-4 mb-1 sm:w-full sm:h-auto sm:m-1 md:w-96  lg:w-11/12 lg:mb-0 xl:11/12">
            <!-- <h2 class="text-center text-white font-serif text-4xl font-black">BarCode Detector</h2> -->
            <video id="camera" class="border-2  rounded-md w-full sm:w-10/12 sm:mx-9 sm:my-10 lg:11/12 lg:h-96 "></video>
        </div>
       <!-- outpurt will be shown here -->
        <div class="w-10/12 h-auto mx-12 space-y-4  mb-1 sm:w-full sm:h-auto sm:m-1 md:w-10/12 lg:w-11/12 lg:mb-0  xl:11/12">
        <!-- <h2 class="text-center text-white font-serif text-4xl font-black">BarCode</h2> -->
        <h2 class="text-justify text-white font-serif ">Bar Code:</h2>
            <div class="h-11  border-2 rounded-md w-full sm:h-8  mx-1 my-1 lg:w-9/12  lg:h-14">
              <h2 id="scandata" class="text-center text-white font-serif text-4xl font-black"></h2>
            </div>
            <div class="w-full rounded-md lg:w-9/12  lg:h-72">
            <h2 class="text-justify text-white font-serif ">Capture Image:</h2>
              <canvas id="canvas" class="w-full border-2  rounded-md h-full "></canvas>
              <img id="photo" alt="no image" class="hidden lg:w-6/12">
            </div>
        </div>
        <!-- <button type="button" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-full text-sm px-1 py-2.5 text-center" id="btnScanner">Scan Barcode</button>
        <button type="button" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-full text-sm px-1 py-2.5 text-center" id="btnCapture" onclick="captureVideo()">Capture Image</button> -->
    </div><br>
    <div class="mx-32 sm:w-2/3 sm:mx-64  md:mx-56 lg:mx-96 xl:mx-96">
        <img src="image/bar.png"    class="float-left mx-2.5 sm:w-20 h-16 sm:mx-1 md:mx-3 lg:mx-4 2xl:mx-8" id="btnScanner"/>
        <img src="image/camera.png" class="float-left mx-2.5 sm:w-20 h-16 sm:mx-1 md:mx-3 lg:mx-4 2xl:mx-8" id="btnCapture" onclick="captureImage()" />
        <button type="button" class="float-left h-16 w-20 bg-blue-700 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-full 2xl:mx-8" id="btnSubmit">Submit</button>  
    </div>
    <!-- sm:row-span-1  md:row-span-2 box-content  md:h-96 md:w-96  p-4 border-4
    sm:h-12   sm:row-span-1  md:row-span-2 box-content  md:h-96 md:w-96  p-4 border-4  -->
    <script>
     const video = document.getElementById('camera');
    var model, embeddings;
    const result = document.getElementById('result');
    const photo = document.getElementById('photo');
    const thresh = 0.5;
    var btnCapture = document.getElementById("btnCapture");
    var btnScanner = document.getElementById("btnScanner");
    var btnSubmit = document.getElementById("btnSubmit");

    btnCapture.addEventListener("click", captureImage);
    btnScanner.addEventListener("click", scanbarcode);
    btnSubmit.addEventListener("click", make_predition);

    navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => {
        video.srcObject = stream;
        video.play();
      })

    // JavaScript
    function scanbarcode(){
      document.getElementById("scandata").innerHTML = "";
        // Initialize QuaggaJS to decode barcodes
        Quagga.init({
        inputStream: {
            name: "Live",
            type: "LiveStream",
            target: video
        },
        decoder: {
            readers: [
                "code_128_reader"
                // "ean_reader",
                // "ean_8_reader",
                // "code_39_reader",
                // "code_39_vin_reader",
                // "codabar_reader",
                // "upc_reader",
                // "upc_e_reader",
                // "i2of5_reader",
                // "2of5_reader",
                // "code_93_reader",
                // "code_32_reader"
            ]  
        }
        }, (err) => {
        if (err) {
            console.log(err);
            return;
        }
        console.log('Initialization finished, Ready to start');
        Quagga.start();
        })
        Quagga.onDetected((val) => {
            // console.log(val.codeResult)
            var  scandata = val.codeResult
            console.log(scandata);
            document.getElementById("scandata").innerHTML = scandata.code;
            Quagga.stop();
        })
    // 
  };
  
  function captureImage() {
    var canvas = document.getElementById('canvas');
        var context = canvas.getContext('2d');  
        width = video.videoWidth;
        height = video.videoHeight;
        canvas.setAttribute('width', width);
        canvas.setAttribute('height', height);
        // Capture the image into canvas from Webcam streaming Video element  
        context.drawImage(video, 0, 0, width, height); 
        var data = canvas.toDataURL('image/png');
        photo.setAttribute('src', data); 
  }

    async function init() {
    model = await tf.loadGraphModel('https://generalai.in/facerec_js/model/model.json');
    }

    function pre_process(tfTensor){
      tfTensor = tf.image.resizeBilinear(tfTensor, [160,160]).toFloat();
      tfTensor = tfTensor.div(tf.scalar(255.0)).expandDims();
      return tfTensor;
    }

    function make_predition(){
        img = document.getElementById("photo");
        let tfTensor = tf.browser.fromPixels(img);
        tfTensor = pre_process(tfTensor);
        img_embedding = model.execute([tfTensor]);
        img_embedding = img_embedding.squeeze();
        fetch('https://generalai.in/facerec_js/db/mean_embeddings.json')
        .then(res => res.json())
        .then(embeddings => {
          label = [];
          sim_score = [];

          for(let person in embeddings){
            let per_embedding = embeddings[person];
            per_embedding = tf.tensor1d(per_embedding);
            cos_sim = tf.abs(tf.metrics.cosineProximity(img_embedding, per_embedding)).dataSync()[0];
            sim_score.push(cos_sim);
            label.push(person);
          }

          label_idx = tf.argMax(tf.tensor1d(sim_score), -1).dataSync()[0];
          max_score = sim_score[label_idx]
          if(max_score > thresh){
            alert("Detected: "+label[label_idx]+" sim_score: "+max_score);
          }
          else{
            alert("Detected: unkown!");
          }
        })
    }
    init();
    </script>
    </body>
</html>