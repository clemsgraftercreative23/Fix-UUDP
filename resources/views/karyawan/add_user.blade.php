@extends('template.app')

@section('content')

<div class="page-content">

  <div class="row">
      <div class="col">
          <div class="card">
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <p class="card-title" style="color:#62d49e;">Dashboard</p>
                    <label class="card-title" style="font-size:20px; color:#62d49e;">Add User Application</label>
                  </div>
                </div>

                <form id="sample_form_add">
                  @csrf
                      <input type="hidden" name="action" id="action" />
                      <div class="form-group">
                          <label>Kode Projek</label>
                          <input type="text" class="form-control" id="no_project" name="no_project"  placeholder="Masukkan nomor atau kode projek" required>
                      </div>
                      <div class="form-group">
                          <label>Nama Projek</label>
                          <input type="text" class="form-control" id="nama" name="nama" placeholder="Masukkan nama projek" required>
                      </div>
                      <div class="form-group">
                          <label>Deskripsi</label>
                          <textarea class="form-control" name="keterangan" id="keterangan" placeholder="Masukkan Deskripsi Projek" required></textarea>
                      </div>
                      </div>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                          <button type="submit" class="btn btn-primary" name="action_button_add" id="action_button_add">Simpan</button>
                      </div>
                </form>

              </div>
          </div>
      </div>
  </div>

  
  
@push('scripts')

@endpush
@endsection
