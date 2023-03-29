<!DOCTYPE html>
<html> 
    <head>
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <link rel="stylesheet" href="css/index.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.2.0/dist/tf.min.js"> </script>
        <script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2@1.2.6/dist/quagga.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
    </head>
    <body>
        <div class="container_box">
            <div class="video_box">
            <h2 class="barcode_txt">BarCode Detector</h2>
            <video id="camera" class="video"></video>
            </div>
            <div class="output_box">

            </div>
        </div>
        <div class="row">
        <i class="fa fa-camera" style="font-size:48px;color:blue"></i>
        </div>
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