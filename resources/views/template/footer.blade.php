<div class="page-footer">
                    <div class="row">
                        <div class="col-md-12">
                            <!-- <span class="footer-text">Copyright &copy; <script>document.write(new Date().getFullYear())</script> Cash Advance & Reimbusement App ( UUDP : v1.0.2 ) </span> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

<!-- Javascripts -->
        <!-- <script src="{{asset('assets/plugins/jquery/jquery-3.4.1.min.js')}}"></script> -->
        <script src="{{asset('assets/plugins/bootstrap/popper.min.js')}}"></script>
        <script src="{{asset('assets/plugins/bootstrap/js/bootstrap.min.js')}}"></script>
        <script src="{{asset('assets/plugins/jquery-slimscroll/jquery.slimscroll.min.js')}}"></script>
        <script src="{{asset('assets/plugins/jquery-sparkline/jquery.sparkline.min.js')}}"></script>
        <script src="{{asset('assets/plugins/apexcharts/dist/apexcharts.min.js')}}"></script>
        <script src="{{asset('assets/plugins/blockui/jquery.blockUI.js')}}"></script>
        <script src="{{asset('assets/plugins/flot/jquery.flot.min.js')}}"></script>
        <script src="{{asset('assets/plugins/flot/jquery.flot.time.min.js')}}"></script>
        <script src="{{asset('assets/plugins/flot/jquery.flot.symbol.min.js')}}"></script>
        <script src="{{asset('assets/plugins/flot/jquery.flot.resize.min.js')}}"></script>
        <script src="{{asset('assets/plugins/flot/jquery.flot.tooltip.min.js')}}"></script>
        <script src="{{asset('assets/plugins/select2/js/select2.full.min.js')}}"></script>
        <script src="{{asset('assets/js/connect.min.js')}}"></script>
        <!-- <script src="{{asset('assets/js/pages/select2.js')}}"></script> -->
        <!-- <script src="{{asset('assets/js/pages/dashboard.js')}}"></script> -->
        <script src="{{asset('assets/plugins/DataTables/datatables.min.js')}}"></script>
        <script src="{{asset('assets/js/pages/datatables.js')}}"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.js"></script>
        <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

        <script type="text/javascript">
        $(document).ready(function(){
            $('.side-close').click(function(){
                $('.no-loader').toggleClass('small-screen-sidebar-active');
            });
        });
                
        $(document).ready(function(){
           
            // Toggle the visibility of .show-search when .search-btn is clicked
            $('.search-btn').click(function(event){
                event.stopPropagation();
                $('.show-search').toggleClass('active');
            });

            // Hide .show-search when clicking outside of it
            $(document).click(function(event) { 
                if(!$(event.target).closest('.show-search').length && !$(event.target).hasClass('search-btn')) {
                    $('.show-search').removeClass('active');
                }        
            });

        });

        // CHART LINE

        @if(Request::segment(1)=='' || Request::segment(1)=='home' || Request::segment(1)=='home-all')

            // REIMBURSEMENT
            var ctx = document.getElementById("myChart").getContext('2d');
            var year = "{{date('Y')}}";
            var myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September","Oktober", "November", "Desember"],
                    datasets: [{
                        label: 'Inquiry '+year+'', 
                        data: [{{$jan}}, {{$feb}}, {{$mar}}, {{$apr}}, {{$mei}}, {{$jun}}, {{$jul}}, {{$ags}}, {{$sept}}, {{$okt}}, {{$nov}}, {{$des}}], 
                        fill: false,
                        borderColor: '#62d49c', 
                        backgroundColor: '#62d49c', 
                        borderWidth: 1 
                    }]},
                options: {
                  responsive: true, 
                  maintainAspectRatio: false, 
                }
            });

            //CASH ADVANCE

            var ctx = document.getElementById("myChart1").getContext('2d');
            var year = "{{date('Y')}}";
            var myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September","Oktober", "November", "Desember"],
                    datasets: [{
                        label: 'Inquiry '+year+'', 
                        data: [{{$jan_cash}}, {{$feb_cash}}, {{$mar_cash}}, {{$apr_cash}}, {{$mei_cash}}, {{$jun_cash}}, {{$jul_cash}}, {{$ags_cash}}, {{$sep_cash}}, {{$okt_cash}}, {{$nov_cash}}, {{$des_cash}}], 
                        fill: false,
                        borderColor: '#62d49c', 
                        backgroundColor: '#62d49c', 
                        borderWidth: 1 
                    }]},
                options: {
                  responsive: true, 
                  maintainAspectRatio: false, 
                }
            });

            // SETTLEMENT REIMBURSEMENT
            var DrawSparkline = function() {
            $('#sparkline-chart-1').sparkline([{{$jan_set}}, {{$feb_set}}, {{$mar_set}}, {{$apr_set}}, {{$mei_set}}, {{$jun_set}}, {{$jul_set}}, {{$ags_set}}, {{$sept_set}}, {{$okt_set}}, {{$nov_set}}, {{$des_set}}], {
                    type: 'line',
                    width: '100%',
                    height: '100',
                    chartRangeMax: 50,
                    lineColor: '#62d49c',
                    fillColor: '#a0e7c4',
                    highlightLineColor: 'transparent',
                    highlightSpotColor: 'transparent',
                    maxSpotColor: 'transparent',
                    spotColor: 'transparent',
                    minSpotColor: 'transparent',
                    lineWidth: 3
                });
            };

            DrawSparkline();

            // SETTLEMENT CASH ADVANCE
            var DrawSparkline1 = function() {
            $('#sparkline-chart-2').sparkline([{{$jan_cash_set}}, {{$feb_cash_set}}, {{$mar_cash_set}}, {{$apr_cash_set}}, {{$mei_cash_set}}, {{$jun_cash_set}}, {{$jul_cash_set}}, {{$ags_cash_set}}, {{$sep_cash_set}}, {{$okt_cash_set}}, {{$nov_cash_set}}, {{$des_cash_set}}], {
                    type: 'line',
                    width: '100%',
                    height: '100',
                    chartRangeMax: 50,
                    lineColor: '#62d49c',
                    fillColor: '#a0e7c4',
                    highlightLineColor: 'transparent',
                    highlightSpotColor: 'transparent',
                    maxSpotColor: 'transparent',
                    spotColor: 'transparent',
                    minSpotColor: 'transparent',
                    lineWidth: 3
                });
            };

            DrawSparkline1();

        @endif
        </script>

        @stack('scripts')
