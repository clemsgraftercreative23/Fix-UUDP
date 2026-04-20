<div class="page-sidebar">
  <a  id="sidebar-close" href="#" class="my-3 btn btn-danger side-close ">
  <i class="fa fa-times" aria-hidden="true"></i>
  </a>
                <div class="logo-box">
                    <center>
                        <img src="{{asset('assets/images/logo-uudp.png')}}" alt="logo uudp" style="width: 80px; height: 80px;">
                    </center>
                    
                    <br>
                    <center><span style="text-align: center; color: #62d49e;"><strong>{{Auth::user()->name}}<br>
                  
                            @if(Auth::user()->jabatan=='Owner')
                                Finance
                            @elseif(Auth::user()->jabatan=='Finance')
                                HR GA
                            @elseif(Auth::user()->jabatan=='Finance Supervisor')
                                Finance Supervisor
                            @elseif(Auth::user()->jabatan=='Direktur Operasional')
                                Head Department
                            @elseif(Auth::user()->jabatan=='karyawan')
                                Employee
                            @else 
                                Admin
                            @endif
                            
                            
                            
                    
                        </strong></span>
                    </center>
                    <hr>
                </div>
                <?php  $segment1 =  Request::segment(1);?>
                <div class="page-sidebar-inner slimscroll" style="margin-top: -50px">
                     @if(Auth::user()->jabatan=='superadmin')
                    <ul class="accordion-menu">
                        <li class="sidebar-title">
                            Menu
                        </li>
                        @if($segment1 == 'home')
                        <li class="active-page">
                          @else
                          <li>
                          @endif
                            <a href="{{url('home')}}" class="active"><i class="material-icons-outlined">apps</i>Dashboard </a>
                        </li>
                        
                        @if($segment1 == 'pengajuan' || $segment1 == 'pencairan' || $segment1 == 'pertanggungjawaban')
                           <li class="active-page open">
                        @else
                            <li>
                        @endif
                            <a href="#"><i class="material-icons">account_balance_wallet</i>Cash Advance<i class="material-icons has-sub-menu">add</i></a>
                            <ul class="sub-menu" style="display: none;">
                                <li>
                                  <a href="{!!url('pengajuan')!!}">Inquiry</a>
                                </li>
                                <li>
                                  <a href="{!!url('pencairan')!!}">Settlement</a>
                                </li>
                                <li>
                                  <a href="{!!url('pertanggungjawaban')!!}">Accountability Report</a>
                                </li>
                            </ul>
                        </li>
                        @if($segment1 == 'reimbursement')
                        <li class="active-page open">
                         @else
                         <li>
                         @endif
                         <a href="#"><i class="material-icons">money</i>Reimbursement<i class="material-icons has-sub-menu">add</i></a>
                         <ul class="sub-menu" style="display: none;">
                             <li>
                               <a href="/reimbursement-driver">Driver</a>
                             </li>
                             <li>
                               <a href="/reimbursement-travel">Travel</a>
                             </li>
                             <li>
                               <a href="/reimbursement-entertaiment">Entertainment</a>
                             </li>
                             {{-- <li>
                               <a href="/reimbursement-medical">Medical</a>
                             </li> --}}
                         </ul>
                        </li>
                        
                        @if($segment1 == 'profile')
                          <li class="active-page">
                        @else
                        <li>
                          @endif
                          <a href="{{url('profile')}}"><i class="material-icons">account_circle</i>Profile Setting</a>
                        </li>
                      

                        <li>
                           <a href="#"><i class="material-icons">settings</i>Setting<i class="material-icons has-sub-menu">add</i></a>
                           <ul class="sub-menu">
                               
                               @if($segment1 == 'karyawan')
                                  <li class="active-page">
                               @else
                                  <li>
                               @endif
                                   <a href="{{url('karyawan')}}">Employee</a>
                               </li>
                               @if($segment1 == 'kelompok_kegiatan')
                                  <li class="active-page">
                               @else
                                  <li>
                               @endif
                                   <a href="{{url('kelompok_kegiatan')}}">Activity Master</a>
                               </li>
                               @if($segment1 == 'daftar_rencana')
                                  <li class="active-page">
                               @else
                                  <li>
                               @endif
                                   <a href="{{url('daftar_rencana')}}">Sub Activity</a>
                               </li>
                               @if($segment1 == 'project')
                                  <li class="active-page">
                               @else
                                  <li>
                               @endif
                                   <a href="{{url('project')}}">Project</a>
                               </li>
                               @if($segment1 == 'kasandbank')
                                  <li class="active-page">
                               @else
                                  <li>
                               @endif
                                   <a href="{{url('kasandbank')}}">Cash & Equivalents</a>
                               </li>
                               @if($segment1 == 'listkasbank')
                                  <li class="active-page">
                               @else
                                  <li>
                               @endif
                                   <a href="{{url('listkasbank')}}">Sub Cash & Equivalents</a>
                               </li>
                               @if($segment1 == 'departemen')
                                  <li class="active-page">
                               @else
                                  <li>
                               @endif
                                   <a href="{{url('departemen')}}">Department</a>
                               </li>
                               @if($segment1 == 'saldoharian')
                                  <li class="active-page">
                               @else
                                  <li>
                               @endif
                                   <a href="{{url('saldoharian')}}">Balance</a>
                               </li>
                               @if($segment1 == 'user_aplikasi')
                                  <li class="active-page">
                               @else
                                  <li>
                               @endif
                                   <a href="{{url('user_aplikasi')}}">User Application</a>
                               </li>
                           </ul>
                       </li>
                        <li>
                        <li>
                          <a href="{{url('accurate/authorize')}}" target="_blank"><i class="material-icons">share</i>Sync Accurate</a>
                        </li>
                            <br><br><br><br>
                            <a href="{{ route('logout') }}" class="btn btn-primary btn-lg btn-block" onclick="event.preventDefault(); document.getElementById('frm-logout').submit();">
                                          <i class="fa fa-power-off"></i> 
                                           {{ __('Sign Out') }}
                            </a>
                      <form id="frm-logout" action="{{ route('logout') }}" method="POST" style="display: none;">
                          {{ csrf_field() }}
                      </form>
                        </li>
                    </ul>
                    @elseif(Auth::user()->jabatan=='karyawan')
                   <ul class="accordion-menu">
                       <li class="sidebar-title">
                           Menu
                       </li>
                       @if($segment1 == 'home')
                       <li class="active-page">
                         @else
                         <li>
                         @endif
                           <a href="{{url('home')}}" class="active"><i class="material-icons-outlined">apps</i>Dashboard </a>
                       </li>
                       
                       @if($segment1 == 'pengajuan' || $segment1 == 'pencairan' || $segment1 == 'pertanggungjawaban')
                           <li class="active-page open">
                        @else
                            <li>
                        @endif
                            <a href="#"><i class="material-icons">account_balance_wallet</i>Cash Advance<i class="material-icons has-sub-menu">add</i></a>
                            <ul class="sub-menu" style="display: none;">
                                <li>
                                  <a href="{!!url('pengajuan')!!}">Inquiry</a>
                                </li>
                                <li>
                                  <a href="{!!url('pencairan')!!}">Settlement</a>
                                </li>
                                <li>
                                  <a href="{!!url('pertanggungjawaban')!!}">Accountability Report</a>
                                </li>
                            </ul>
                        </li>
                       
{{--                        
                         <a href="{{url('reimbursement')}}"><i class="material-icons">money</i>Reimbursement</a>
                       </li> --}}
                       @if($segment1 == 'reimbursement')
                       <li class="active-page open">
                        @else
                        <li>
                        @endif
                        <a href="#"><i class="material-icons">money</i>Reimbursement<i class="material-icons has-sub-menu">add</i></a>
                        <ul class="sub-menu" style="display: none;">
                            <li>
                              <a href="/reimbursement-driver">Driver</a>
                            </li>
                            <li>
                              <a href="/reimbursement-travel">Travel</a>
                            </li>
                            <li>
                              <a href="/reimbursement-entertaiment">Entertainment</a>
                            </li>
                            {{-- <li>
                              <a href="/reimbursement-medical">Medical</a>
                            </li> --}}
                        </ul>
                    </li>
                       @if($segment1 == 'profile')
                       <li class="active-page">
                         @else
                         <li>
                         @endif
                           <a href="{{url('profile')}}"><i class="material-icons">account_circle</i>Profil</a>
                       </li>

                       <li>
                           <br><br><br><br>
                           <a href="{{ route('logout') }}" class="btn btn-primary btn-lg btn-block" onclick="event.preventDefault(); document.getElementById('frm-logout').submit();">
                                         <i class="fa fa-share"></i>
                                         {{ __('Logout') }}
                           </a>
                     <form id="frm-logout" action="{{ route('logout') }}" method="POST" style="display: none;">
                         {{ csrf_field() }}
                     </form>
                       </li>
                   </ul>
                    @elseif(Auth::user()->jabatan=='Direktur Operasional')
                    <ul class="accordion-menu">
                        <li class="sidebar-title">
                            Menu
                        </li>
                        @if($segment1 == 'home')
                        <li class="active-page">
                          @else
                          <li>
                          @endif
                            <a href="{{url('home')}}" class="active"><i class="material-icons-outlined">apps</i>Dashboard </a>
                        </li>
                        
                        @if($segment1 == 'pengajuan' || $segment1 == 'pencairan' || $segment1 == 'pertanggungjawaban')
                           <li class="active-page open">
                        @else
                            <li>
                        @endif
                            <a href="#"><i class="material-icons">account_balance_wallet</i>Cash Advance<i class="material-icons has-sub-menu">add</i></a>
                            <ul class="sub-menu" style="display: none;">
                                <li>
                                  <a href="{!!url('pengajuan')!!}">Inquiry</a>
                                </li>
                                <li>
                                  <a href="{!!url('pencairan')!!}">Settlement</a>
                                </li>
                                <li>
                                  <a href="{!!url('pertanggungjawaban')!!}">Accountability Report</a>
                                </li>
                            </ul>
                        </li>
                        
                        {{-- @if($segment1 == 'reimbursement')
                        <li class="active-page">
                        @else --}}
                        {{-- <li>
                        @endif
                          <a href="{{url('reimbursement')}}"><i class="material-icons">money</i>Reimbursement</a>
                        </li>   --}}
                        @if($segment1 == 'reimbursement')
                       <li class="active-page open">
                        @else
                        <li>
                        @endif
                        <a href="#"><i class="material-icons">money</i>Reimbursement<i class="material-icons has-sub-menu">add</i></a>
                        <ul class="sub-menu" style="display: none;">
                            <li>
                              <a href="/reimbursement-driver">Driver</a>
                            </li>
                            <li>
                              <a href="/reimbursement-travel">Travel</a>
                            </li>
                            <li>
                              <a href="/reimbursement-entertaiment">Entertainment</a>
                            </li>
                            {{-- <li>
                              <a href="/reimbursement-medical">Medical</a>
                            </li> --}}
                        </ul>
                    </li>     
                        <!-- @if($segment1 == 'pertanggungjawaban')
                        <li class="active-page">
                          @else
                          <li>
                          @endif
                            <a href="{{url('pertanggungjawaban')}}"><i class="material-icons">verified_user</i>Proses Approved</a>
                        </li> -->
                        @if($segment1 == 'profile')
                        <li class="active-page">
                          @else
                          <li>
                          @endif
                            <a href="{{url('profile')}}"><i class="material-icons">account_circle</i>Profil</a>
                        </li>
                        <li>
                            <br><br><br><br>
                            <a href="{{ route('logout') }}" class="btn btn-primary btn-lg btn-block" onclick="event.preventDefault(); document.getElementById('frm-logout').submit();"><i class="fa fa-share"></i>{{ __('Logout') }}
                            </a>
                            <form id="frm-logout" action="{{ route('logout') }}" method="POST" style="display: none;">
                                {{ csrf_field() }}
                            </form>
                        </li>
                        
                    </ul>
                    @elseif(Auth::user()->jabatan=='Finance' || Auth::user()->jabatan=='Finance Supervisor')
                    <ul class="accordion-menu">
                        <li class="sidebar-title">
                            Menu
                        </li>
                        @if($segment1 == 'home')
                        <li class="active-page">
                          @else
                          <li>
                          @endif
                            <a href="{{url('home')}}" class="active"><i class="material-icons-outlined">apps</i>Dashboard </a>
                        </li>
                        
                        
                        @if($segment1 == 'pengajuan' || $segment1 == 'pencairan' || $segment1 == 'pertanggungjawaban')
                           <li class="active-page open">
                        @else
                            <li>
                        @endif
                            <a href="#"><i class="material-icons">account_balance_wallet</i>Cash Advance<i class="material-icons has-sub-menu">add</i></a>
                            <ul class="sub-menu" style="display: none;">
                                <li>
                                  <a href="{!!url('pengajuan')!!}">Inquiry</a>
                                </li>
                                <li>
                                  <a href="{!!url('pencairan')!!}">Settlement</a>
                                </li>
                                <li>
                                  <a href="{!!url('pertanggungjawaban')!!}">Accountability Report</a>
                                </li>
                            </ul>
                        </li>
                        
                        {{-- @if($segment1 == 'reimbursement')
                       <li class="active-page">
                         @else
                         <li>
                         @endif
                         <a href="{{url('reimbursement')}}"><i class="material-icons">money</i>Reimbursement</a>
                       </li> --}}
                       @if($segment1 == 'reimbursement')
                       <li class="active-page open">
                        @else
                        <li>
                        @endif
                        <a href="#"><i class="material-icons">money</i>Reimbursement<i class="material-icons has-sub-menu">add</i></a>
                        <ul class="sub-menu" style="display: none;">
                            <li>
                              <a href="/reimbursement-driver">Driver</a>
                            </li>
                            <li>
                              <a href="/reimbursement-travel">Travel</a>
                            </li>
                            <li>
                              <a href="/reimbursement-entertaiment">Entertainment</a>
                            </li>
                            {{-- <li>
                              <a href="/reimbursement-medical">Medical</a>
                            </li> --}}
                        </ul>
                    </li>
                       @if($segment1 == 'pencairan-reimbursement')
                       <li class="active-page">
                         @else
                         <li>
                         @endif
                         <!--<a href="{{url('pencairan-reimbursement')}}"><i class="material-icons">download</i>Settlement Reimbursement</a>-->
                       </li>
                        @if($segment1 == 'profile')
                        <li class="active-page">
                          @else
                          <li>
                          @endif
                            <a href="{{url('profile')}}"><i class="material-icons">account_circle</i>Profil</a>
                        </li>
                        <!--<li>-->
                        <!--  <a href="{{url('accurate/authorize')}}" target="_blank"><i class="material-icons">share</i>Sync Accurate</a>-->
                        <!--</li>                    -->
                        <li>
                            <br><br><br><br>
                            <a href="{{ route('logout') }}" class="btn btn-primary btn-lg btn-block" onclick="event.preventDefault(); document.getElementById('frm-logout').submit();"><i class="fa fa-share"></i>{{ __('Logout') }}
                            </a>
                            <form id="frm-logout" action="{{ route('logout') }}" method="POST" style="display: none;">
                                {{ csrf_field() }}
                            </form>
                        </li>
                    </ul>
                    @elseif(Auth::user()->jabatan=='Owner')
                    <ul class="accordion-menu">
                        <li class="sidebar-title">
                            Menu
                        </li>
                        @if($segment1 == 'home')
                        <li class="active-page">
                          @else
                          <li>
                          @endif
                            <a href="{{url('home')}}" class="active"><i class="material-icons-outlined">apps</i>Dashboard </a>
                        </li>
                        
                        @if($segment1 == 'pengajuan' || $segment1 == 'pencairan' || $segment1 == 'pertanggungjawaban')
                           <li class="active-page open">
                        @else
                            <li>
                        @endif
                            <a href="#"><i class="material-icons">account_balance_wallet</i>Cash Advance<i class="material-icons has-sub-menu">add</i></a>
                            <ul class="sub-menu" style="display: none;">
                                <li>
                                  <a href="{!!url('pengajuan')!!}">Inquiry</a>
                                </li>
                                <li>
                                  <a href="{!!url('pencairan')!!}">Settlement</a>
                                </li>
                                <li>
                                  <a href="{!!url('pertanggungjawaban')!!}">Accountability Report</a>
                                </li>
                            </ul>
                        </li>
                        
                        
                        {{-- @if($segment1 == 'reimbursement') --}}
                        {{-- <li class="active-page">
                          @else
                          <li>
                          @endif
                          <a href="{{url('reimbursement')}}"><i class="material-icons">money</i>Reimbursement</a>
                        </li> --}}
                        @if($segment1 == 'reimbursement')
                       <li class="active-page open">
                        @else
                        <li>
                        @endif
                        <a href="#"><i class="material-icons">money</i>Reimbursement<i class="material-icons has-sub-menu">add</i></a>
                        <ul class="sub-menu" style="display: none;">
                            <li>
                              <a href="/reimbursement-driver">Driver</a>
                            </li>
                            <li>
                              <a href="/reimbursement-travel">Travel</a>
                            </li>
                            <li>
                              <a href="/reimbursement-entertaiment">Entertainment</a>
                            </li>
                            <li>
                              <a href="{{url('pencairan-reimbursement')}}">Settlement</a>
                            </li>
                            {{-- <li>
                              <a href="/reimbursement-medical">Medical</a>
                            </li> --}}
                        </ul>
                    </li>
                    <!--<li>-->
                    <!--    <a href="{{url('pencairan-reimbursement')}}"><i class="material-icons">download</i>Settlement Reimb</a>-->
                    <!--</li>-->
                    <li>
                        <a href="{{url('accurate/authorize')}}" target="_blank"><i class="material-icons">share</i>Sync Accurate</a>
                    </li>  
                    
                        @if($segment1 == 'profile')
                        <li class="active-page">
                          @else
                          <li>
                          @endif
                            <a href="{{url('profile')}}"><i class="material-icons">account_circle</i>Profil</a>
                        </li>
                        <br><br>
                        <li>
                            <a href="{{ route('logout') }}" class="btn btn-primary btn-lg btn-block" onclick="event.preventDefault(); document.getElementById('frm-logout').submit();"><i class="fa fa-share"></i>{{ __('Logout') }}
                            </a>
                            <form id="frm-logout" action="{{ route('logout') }}" method="POST" style="display: none;">
                                {{ csrf_field() }}
                            </form>
                        </li>
                    </ul>
                    @endif
                </div>
            </div>
