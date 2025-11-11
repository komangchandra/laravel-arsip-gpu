<section>
    <div class="row justify-content-start">
        <div class="col-lg-12 col-md-12">
            <div class="mb-4">
                <h2 class="h5 text-danger font-weight-bold">
                    {{ __('Hapus Akun') }}
                </h2>
                <p class="text-muted small mb-0">
                    {{ __('Setelah akun Anda dihapus, semua data akan dihapus secara permanen. Pastikan Anda telah menyimpan data penting sebelum melanjutkan.') }}
                </p>
            </div>

            <!-- Tombol Hapus -->
            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#confirmUserDeletionModal">
                <i class="fas fa-user-times mr-1"></i> {{ __('Hapus Akun') }}
            </button>

            <!-- Modal Konfirmasi -->
            <div class="modal fade" id="confirmUserDeletionModal" tabindex="-1" role="dialog" aria-labelledby="confirmUserDeletionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="confirmUserDeletionModalLabel">
                                {{ __('Konfirmasi Penghapusan Akun') }}
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('profile.destroy') }}">
                            @csrf
                            @method('delete')

                            <div class="modal-body">
                                <p>
                                    {{ __('Apakah Anda yakin ingin menghapus akun ini? Setelah dihapus, semua data dan sumber daya Anda akan hilang secara permanen.') }}
                                </p>

                                <div class="form-group mt-3">
                                    <label for="password">{{ __('Masukkan Password untuk Konfirmasi') }}</label>
                                    <input
                                        id="password"
                                        name="password"
                                        type="password"
                                        class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                                        placeholder="{{ __('Password') }}"
                                        required
                                    >
                                    @error('password', 'userDeletion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                    {{ __('Batal') }}
                                </button>
                                <button type="submit" class="btn btn-danger">
                                    {{ __('Hapus Akun') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
