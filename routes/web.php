  <?php

  /*
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can regizster web routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
  */

  Route::get('/welcome', function () {
      return view('welcome');
  });
  Route::get('masuk', function () {
      event(new App\Events\StatusLiked('Someone'));
      return view('yea');

  });
  Auth::routes();
  // Route::get('/', function () {
  //     return view('welcome');
  // });
  Route::get('/', 'HomeController@index')->name('home');
  Route::get('/home', 'HomeController@index')->name('home');
  Route::get('/home-all', 'HomeController@showAll');

  // Route::get('/oauth/authorize', 'TestController@accurateAuthorize');
  Route::get('/accurate/authorize', 'OauthController@accurateAuthorize');
  Route::get('/accurate-callback', 'OauthController@callback');

  // home
  Route::get('/filter/graphic/{id}','HomeController@filtergrap');
  Route::get('/filter/totalfilter/{id}','HomeController@totalfilter');


  // karyawan
  Route::resource('/karyawan', 'KaryawanController');
  Route::get('/karyawan/profile/{id}','KaryawanController@show');
  Route::get('/profile','KaryawanController@profile');
  Route::post('update-karyawan','KaryawanController@updateKaryawan');
  Route::post('delete-karyawan','KaryawanController@deleteKaryawan');

  // project
  Route::resource('/project', 'ProjectController');
  Route::post('/syncProject', 'ProjectController@syncProject')->name('syncProject');
  Route::post('project/update', 'ProjectController@update')->name('project.update');
  Route::get('project/destroy/{id}', 'ProjectController@destroy');
  Route::post('/getProject', 'ProjectController@getProject')->name('getProject');
  Route::get('daftar_rencana/get/{id}','ProjectController@getKelompok');

  // kelompok kegiatan
  Route::resource('/kelompok_kegiatan', 'KelompokkegiatanController');
  Route::post('/syncKelompok', 'KelompokkegiatanController@syncKelompok')->name('syncKelompok');
  // daftar rencana
  Route::resource('/daftar_rencana', 'DaftarRencanaController');
  Route::post('/syncRencana', 'DaftarRencanaController@syncRencana')->name('syncRencana');

  // pengajuan
  Route::get('/pengajuan', 'PengajuanController@index');
  Route::get('/pengajuan/project/{id}','PengajuanController@project');
  Route::post('/pengajuan/add','PengajuanController@store');
  Route::post('/pengajuan/update','PengajuanController@update');
  Route::post('/pengajuan/approvefinace','PengajuanController@approvefinace');
  Route::post('/pengajuan/approveowner','PengajuanController@approveowner');
  Route::get('/abc/{id}/{project}','PengajuanController@searchrencana');
  Route::get('/pengajuan/searchbudget/{proyek}/{kelompok}/{daftar}/{list}','PengajuanController@searchbudget');
  Route::get('/pengajuan/searchkelompok/{id}','PengajuanController@searchkelompok');
  Route::get('/pengajuan/edit/{id}','PengajuanController@edit');
  Route::get('/pengajuan/detail/{id}','PengajuanController@detail');
  Route::get('/pengajuan/tmps/{proyek}/{kelompok}/{daftar}/{list}/{kd}/{ks}/{unik}','PengajuanController@tmp');
  Route::get('/pengajuan/delete/{proyek}/{kelompok}/{daftar}/{list}/{unik}','PengajuanController@deletetmp');
  Route::get('/pengajuan/totalpengajuan','PengajuanController@totalpengajuan');
  Route::post('/pengajuan/proyek','PengajuanController@proyek');

  // pencairan
  Route::get('/pencairan', 'PencairanController@index');
  Route::get('/pencairan/{id}/edit', 'PencairanController@edit');
  Route::get('/insertPencairan/{id}','PencairanController@insertPencairan');
  Route::get('getMetode/{id}','PencairanController@getMetode');
  Route::post('/storePayment','PencairanController@storePayment');
  Route::post('/getDetailPencairan', 'PencairanController@getDetailPencairan')->name('getDetailPencairan');
  Route::get('/pencairan/totalpencairan','PencairanController@totalpencairan');
  Route::get('/pencairan/sisapencairan','PencairanController@sisapencairan');
  Route::get('/pencairan/cektermin/{id}','PencairanController@cektermin');
  Route::get('/pencairan/deletetermin/{id}','PencairanController@delete');
  Route::get('/pencairan/addtermin/{id}','PencairanController@addtermin');
  Route::post('/pencairan/termin','PencairanController@updatedtermin');
  Route::post('/pencairan/push','PencairanController@push');

  // pertanggungjawaban
  Route::resource('/pertanggungjawaban', 'PertanggungjawabanController');
  Route::get('/insertPertanggungjawaban/{id}','PertanggungjawabanController@insertPertanggungjawaban');
  Route::any('/addPertanggungjawaban', 'PertanggungjawabanController@addPertanggungjawaban')->name('addPertanggungjawaban');
Route::post('/storePertanggingjawaban', 'PertanggungjawabanController@store')->name('store');
  Route::post('/getPertanggungjawaban', 'PertanggungjawabanController@getPertanggungjawaban')->name('getPertanggungjawaban');
  Route::post('/changePertanggungjawaban', 'PertanggungjawabanController@changePertanggungjawaban')->name('changePertanggungjawaban');
  Route::get('/pertanggungjawaban/fetchData/{id}', 'PertanggungjawabanController@fetchData');
  Route::post('/pertanggungjawaban/change','PertanggungjawabanController@change');
  Route::delete('/pertanggungjawaban/detail/{id}','PertanggungjawabanController@deleteDetail');
  Route::post('/pertanggungjawaban-finish','PertanggungjawabanController@finish');
  Route::post('/pertanggungjawaban/deleteData', 'PertanggungjawabanController@deleteData')->name('pertanggungjawaban.deleteData');
  Route::post('/pertanggungjawaban/approve/{id}', 'PertanggungjawabanController@approve')->name('pertanggungjawaban.approve');

  // pertanggungjawaban new
  Route::resource('/pertanggungjawabanuudp', 'PertanggungjawabanuudpController');
  Route::post('/getPertanggungjawabanUudp', 'PertanggungjawabanuudpController@getPertanggungjawabanUudp')->name('getPertanggungjawabanUudp');
  Route::get('/insertPertanggungjawabanuudp/{id}','PertanggungjawabanuudpController@insertPertanggungjawaban');

  //KAS DAN BANK
  Route::resource('/kasandbank', 'KasbankController');
  Route::post('/syncKasbank', 'KasbankController@syncKasbank')->name('syncKasbank');

  //KAS DAN BANK
  Route::resource('/listkasbank', 'ListkasbankController');
  Route::post('/syncListkasbank', 'ListkasbankController@syncListkasbank')->name('syncListkasbank');

  // Departemen
  Route::resource('/departemen', 'DepartemenController');
  Route::post('/syncDepartemen', 'DepartemenController@syncDepartemen')->name('syncDepartemen');

  // Saldo Harian
  Route::resource('/saldoharian', 'SaldoharianController');
  Route::get('saldoharian/destroy/{id}', 'SaldoharianController@destroy');


  // karyawan
  Route::resource('/user_aplikasi', 'UseraplikasiController');
  Route::get('add_user', 'UseraplikasiController@add_user');
  Route::post('user_aplikasi/update', 'UseraplikasiController@update')->name('user_aplikasi.update');
  Route::post('user_aplikasi/remove_jabatan', 'UseraplikasiController@remove_jabatan')->name('user_aplikasi.remove_jabatan');
  Route::get('/edit_useraplikasi/{id}','UseraplikasiController@edit_useraplikasi');
  Route::get('fillEmployee/{id}', 'UseraplikasiController@fillEmployee')->name('fillEmployee');

  Route::get('/test', 'TestController@index')->middleware('guest');
  Route::resource('/otorisasi', 'OtorisasiController');
  Route::resource('/jurnal', 'JurnalController');

  Route::resource('reimbursement', 'ReimbursementController');
  Route::resource('pencairan-reimbursement', 'PencairanReimbursementController');
  Route::post('reimbursement/approve/{id}', 'ReimbursementController@approve');
  Route::post('reimbursement/reject/{id}', 'ReimbursementController@reject');

  Route::get('reimbursement-user','ReimbursementController@listUser');
  Route::get('settlement-user','ReimbursementController@listSettlement');
  Route::resource('reimbursement-driver', 'DriverReimbursementController');
  Route::get('get-reimbursement-driver/{id}', 'DriverReimbursementController@getReimbursement');
  Route::put('reimbursement-driver/update-approval/{id}', 'DriverReimbursementController@updateApproval');
  Route::get('reimbursement-driver-approval', 'DriverReimbursementController@approval');
  Route::get('reimbursement-driver-print', 'DriverReimbursementController@print');
  Route::get('reimbursement-driver/approve_multiple/{id}', 'DriverReimbursementController@approveMultiple');
  Route::get('reimbursement-travel-print', 'TravelReimbursementController@print');

  Route::resource('reimbursement-entertaiment', 'EntertaimentReimbursementController');
  Route::get('reimbursement-entertaiment-approval', 'EntertaimentReimbursementController@approval');
  Route::get('reimbursement-entertaiment-print', 'EntertaimentReimbursementController@print');
  Route::get('reimbursement-entertaiment/approve_multiple/{id}', 'EntertaimentReimbursementController@approveMultiple');
  Route::put('reimbursement-entertaiment/update-approval/{id}', 'EntertaimentReimbursementController@updateApproval');

  Route::resource('reimbursement-medical', 'MedicalReimbursementController');

  Route::resource('reimbursement-travel', 'TravelReimbursementController');
  Route::get('edit-travel-inquiry/{id}', 'TravelReimbursementController@editInquiry');
  Route::get('edit-travel-overseas/{id}', 'TravelReimbursementController@editOverseas');
  // Route::get('save-new-item', 'TravelReimbursementController@storeNewItem');
  Route::post('update-travel-inq/{id}', 'TravelReimbursementController@updateInquiry');
  Route::get('reimbursement-travel-overseas/create', 'TravelReimbursementController@createOverseas');
  Route::get('reimbursement-travel-approval', 'TravelReimbursementController@approval');
  Route::get('get-currency/{id}/{cur}', 'TravelReimbursementController@getCurrency');
  Route::get('get-trip-type/{id}', 'TravelReimbursementController@getTripType');
  Route::get('get-trip-type-overseas/{id}', 'TravelReimbursementController@getTripTypeOverseas');
  Route::get('get-travel-trip-rates/{id}', 'TravelReimbursementController@getTravelTripRates');
  Route::get('reimbursement-travel/approve_multiple/{id}', 'TravelReimbursementController@approveMultiple');
  Route::get('reimbursement-travel/add-item/{id_main}', 'TravelReimbursementController@addNewItem');
  Route::get('reimbursement-travel/add-item/{id_main}/{id_travel}', 'TravelReimbursementController@addItem');
  Route::post('reimbursement-travel/update-item/{id_main}/{id_travel}', 'TravelReimbursementController@updateItem');
  Route::post('reimbursement-travel/update-item-reject/{id_main}/{id_travel}', 'TravelReimbursementController@updateItemReject');
  Route::post('reimbursement-travel/update-item-approval/{id_main}/{id_travel}', 'TravelReimbursementController@updateItemApproval');
  Route::post('reimbursement-travel/save-item/{id_main}', 'TravelReimbursementController@saveItem');

  Route::get('export-settlement', 'PencairanReimbursementController@exportSettlement');

  Route::post('update-currency', 'TravelReimbursementController@updateCurrency');
  Route::get('/get-currency-options', 'TravelReimbursementController@getCurrencyOptions');
  Route::post('/delete-currency-options', 'TravelReimbursementController@deleteCurrencyOptions');
  

  
  