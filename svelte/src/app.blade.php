<!doctype html>

<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">



        <link rel="icon" href="https://{{ $envs['BE_HOST'] }}/favicon.ico">



        <!-- Template styles -->
        <link rel="stylesheet" href="https://{{ $envs['BE_HOST'] }}/assets/tpl/sb-admin/dist/css/styles.css">



        <!-- FontAwesome -->
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous" data-auto-replace-svg="nest"></script>

        <!-- Luxon -->
        <script src="https://www.solenoid.it/cdn/lib/js/luxon/luxon.min.js"></script>



        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>



        <!-- Solenoid/HTTP -->
        {{--<script src="https://www.solenoid.it/cdn/lib/js/solenoid/solenoid.http.js"></script>--}}
        <script src="https://{{ $envs['BE_HOST'] }}/assets/lib/solenoid/solenoid.http.js"></script>

        <!-- Solenoid/SSE -->
        <script src="https://www.solenoid.it/cdn/lib/js/solenoid/solenoid.sse.js"></script>

        <!-- Solenoid/URL -->
        <script src="https://www.solenoid.it/cdn/lib/js/solenoid/solenoid.url.js"></script>

        <!-- Solenoid/File -->
        <script src="https://www.solenoid.it/cdn/lib/js/solenoid/solenoid.file.js"></script>

        <!-- Solenoid/LocalStorage -->
        <script src="https://www.solenoid.it/cdn/lib/js/solenoid/solenoid.local_storage.js"></script>

        <!-- Solenoid/DateTime -->
        <script src="https://www.solenoid.it/cdn/lib/js/solenoid/solenoid.datetime.js"></script>



        <!-- ChartJS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>



		%sveltekit.head%
	</head>
	<body>
		%sveltekit.body%



		<!-- Bootstrap -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>



        <div class="swg swg-spinner">
            <div class="spinner-box">
                <div class="spinner-message">
                    <div style="display: flex; flex-direction: row;">
                        <div class="p-4">
                            <img class="app-logo" src="https://{{ $envs['BE_HOST'] }}/assets/images/simba.jpg" alt="">
                        </div>
                        <div class="p-4" style="display: flex; flex-direction: column;">
                            <div class="app-name">{{ $envs['APP_NAME'] }}</div>
                            <div class="app-version">{{ $envs['APP_VERSION'] }}</div>
                            <div class="app-build-time">{{ gmdate( 'c', $envs['APP_BUILD_TIME'] ) }}Z</div>
                        </div>
                    </div>
                </div>
                <div class="spinner-symbol">
                    <div class="loadingio-spinner-dual-ball-i4rbxz429u">
                        <div class="ldio-ludptqkr5l">
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </div>

                    <style type="text/css">
                        @keyframes ldio-ludptqkr5l-o
                        {
                            0%    { opacity: 1; transform: translate(0 0) }
                            49.99% { opacity: 1; transform: translate(77.6px,0) }
                            50%    { opacity: 0; transform: translate(77.6px,0) }
                            100%    { opacity: 0; transform: translate(0,0) }
                        }

                        @keyframes ldio-ludptqkr5l
                        {
                            0% { transform: translate(0,0) }
                            50% { transform: translate(77.6px,0) }
                            100% { transform: translate(0,0) }
                        }

                        .ldio-ludptqkr5l div
                        {
                            position: absolute;
                            width: 77.6px;
                            height: 77.6px;
                            border-radius: 50%;
                            top: 58.199999999999996px;
                            left: 19.4px;
                        }

                        .ldio-ludptqkr5l div:nth-child(1)
                        {
                            background: #018382;
                            animation: ldio-ludptqkr5l 1.923076923076923s linear infinite;
                            animation-delay: -0.9615384615384615s;
                        }

                        .ldio-ludptqkr5l div:nth-child(2)
                        {
                            background: #bfc1c1;
                            animation: ldio-ludptqkr5l 1.923076923076923s linear infinite;
                            animation-delay: 0s;
                        }

                        .ldio-ludptqkr5l div:nth-child(3)
                        {
                            background: #018382;
                            animation: ldio-ludptqkr5l-o 1.923076923076923s linear infinite;
                            animation-delay: -0.9615384615384615s;
                        }

                        .loadingio-spinner-dual-ball-i4rbxz429u
                        {
                            width: 194px;
                            height: 194px;
                            display: inline-block;
                            overflow: hidden;
                            /*background: #f1f2f3;*/
                            background: transparent;
                        }

                        .ldio-ludptqkr5l
                        {
                            width: 100%;
                            height: 100%;
                            position: relative;
                            transform: translateZ(0) scale(1);
                            backface-visibility: hidden;
                            transform-origin: 0 0; /* see note above */
                        }

                        .ldio-ludptqkr5l div { box-sizing: content-box; }
                        /* generated by https://loading.io/ */
                    </style>
                </div>
                <div class="spinner-countdown" hidden></div>
                <div class="spinner-progress" hidden>
                    <div class="spinner-progress-value"></div>
                </div>
            </div>
            <div class="spinner-blackscreen" style="background-color: #737373;"></div>

            <style>

                .swg.swg-spinner
                {
                    position: fixed;
                    z-index: 999999;
                    left: 0;
                    right: 0;
                    top: 0;
                    bottom: 0;
                    display: flex;
                    flex-direction: row;
                    justify-content: center;
                    align-items: center;
                }

                .swg.swg-spinner .spinner-box
                {
                    min-width: 400px;
                    margin: 0 auto;
                    padding: 20px;
                    position: relative;
                    z-index: 1;
                    left: 0;
                    right: 0;
                    top: 0;
                    bottom: 0;
                    display: block;
                    background-color: #959595;
                    border-radius: 4px;
                    box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;
                }

                .swg.swg-spinner .spinner-message
                {
                    padding: 4px 10px;
                    font-size: 12px;
                    font-weight: 600;
                    /*background-color: #cfcfcf;*/
                    color: #000000;
                    border-radius: 4px;
                    text-align: left;
                }

                .swg.swg-spinner .app-logo
                {
                    width: 80px;
                    display: table;
                    border-radius: 4px;
                }

                .swg.swg-spinner .app-name
                {
                    font-weight: 700;
                }

                .swg.swg-spinner .app-version
                {
                    font-weight: 500;
                }

                .swg.swg-spinner .app-build-time
                {
                    font-size: 8px;
                    font-weight: 500;

                    flex-grow: 1;

                    display: flex;
                    align-items: end;
                }

                .swg.swg-spinner .spinner-symbol
                {
                    display: flex;
                    flex-direction: row;
                    justify-content: center;
                    align-items: center;

                    display: none;
                }

                .swg.swg-spinner .spinner-countdown
                {
                    padding: 4px 10px;
                    font-size: 12px;
                    font-weight: 600;
                    background-color: #cfcfcf;
                    color: #000000;
                    border-radius: 4px;
                    text-align: center;
                }

                .swg.swg-spinner .spinner-progress
                {
                    width: 100%;
                    height: 10px;
                    margin-top: 10px;
                    font-size: 12px;
                    font-weight: 600;
                    background-color: #bfc1c1;
                    color: #000000;
                    border-radius: 2px;
                    text-align: center;
                }

                .swg.swg-spinner .spinner-progress-value
                {
                    width: 0%;
                    height: 100%;
                    font-size: 12px;
                    font-weight: 600;
                    background-color: #018382;
                    color: #000000;
                    border-radius: 2px;
                    text-align: center;

                    transition: .2s all ease-in-out;
                }

                .swg.swg-spinner .spinner-blackscreen
                {
                    position: fixed;
                    left: 0;
                    right: 0;
                    top: 0;
                    bottom: 0;
                    background-color: #00000099;

                    animation-name: bgc-change;
                    animation-duration: 1s;
                    animation-iteration-count: infinite;
                }

                @keyframes bgc-change
                {
                    0%
                    {
                        background-color: #00000099;
                    }
                    50%
                    {
                        background-color: #00000089;
                    }
                    100%
                    {
                        background-color: #00000099;
                    }
                }

            </style>
        </div>
	</body>
</html>