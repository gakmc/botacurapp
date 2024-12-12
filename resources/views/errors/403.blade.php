<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ asset('css/403.css') }}">
    <title>Error 403</title>

</head>

<body>
    <div class="container">
        <div class="copy-container center-xy" id="copy-container">
            <p>
                403 | Usuario no autorizado para esta acción.
            </p>
            <span class="handle"></span>
            
        </div>
        <a id="link" href="{{ url()->previous() }}" >Volver Atrás</a>
    </div>


    <svg version="1.1" id="cb-replay" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
        x="0px" y="0px" viewBox="0 0 279.9 297.3" style="enable-background:new 0 0 279.9 297.3;" xml:space="preserve">
        <g>
            <path
                d="M269.4,162.6c-2.7,66.5-55.6,120.1-121.8,123.9c-77,4.4-141.3-60-136.8-136.9C14.7,81.7,71,27.8,140,27.8
          c1.8,0,3.5,0,5.3,0.1c0.3,0,0.5,0.2,0.5,0.5v15c0,1.5,1.6,2.4,2.9,1.7l35.9-20.7c1.3-0.7,1.3-2.6,0-3.3L148.6,0.3
          c-1.3-0.7-2.9,0.2-2.9,1.7v15c0,0.3-0.2,0.5-0.5,0.5c-1.7-0.1-3.5-0.1-5.2-0.1C63.3,17.3,1,78.9,0,155.4
          C-1,233.8,63.4,298.3,141.9,297.3c74.6-1,135.1-60.2,138-134.3c0.1-3-2.3-5.4-5.3-5.4l0,0C271.8,157.6,269.5,159.8,269.4,162.6z" />
        </g>
    </svg>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            let $copyContainer = $("#copy-container"),
                $replayIcon = $('#cb-replay'),
                $copyWidth = $copyContainer.width();
        
            // Simulamos la funcionalidad de SplitText dividiendo el texto en palabras
            function splitText($element, type) {
                let text = $element.text();
                let splitType = type === 'words' ? text.split(" ") : text.split("");
                $element.empty();
        
                $.each(splitType, function(i, val) {
                    if (type === 'words') {
                        $element.append($('<span>').text(val + ' '));  // Añadimos espacio después de cada palabra
                    } else {
                        $element.append($('<span>').text(val));  // Añadimos cada letra como un span
                    }
                });
        
                return $element.find('span');  // Devolvemos todos los span creados
            }
        
            // Dividimos el texto en palabras
            let chars = splitText($copyContainer, 'words');
        
            let splitTextTimeline = gsap.timeline();
            let handleTL = gsap.timeline();
        
            // Animamos el texto
            function animateCopy() {
                splitTextTimeline.staggerFrom(chars, 0.9, {
                    opacity: 0.5, // Usamos una opacidad parcial en lugar de autoAlpha
                    y: 20,       // Un pequeño desplazamiento en y
                    fontSize: "30px",
                    color: "#fff",  // Cambiamos el color inicial
                    ease: "back.out(1.7)"
                }, 0.1)
                .to(chars, {
                    duration: 0.3,
                    fontSize: "30px",
                    color: "red",  // Cambiamos el color final a rojo
                    stagger: 0.1
                });
            }
        
            function blinkHandle() {
                handleTL.fromTo('.handle', 0.4, {
                    autoAlpha: 0
                }, {
                    autoAlpha: 1,
                    repeat: -1,
                    yoyo: true
                }, 0);
            }
        
            function animateHandle() {
                handleTL.to('.handle', 0.7, {
                    x: $copyWidth,
                    ease: "steps(12)"
                }, 0.05);
            }
        
            let tl = gsap.timeline();
            tl.add(function() {
                animateCopy();
                blinkHandle();
            }, 0.2);
        
            // Al hacer clic en el botón de replay
            $('#cb-replay').on('click', function() {
                splitTextTimeline.restart();
                handleTL.restart();
            });
        });
        </script>
        

</body>

</html>