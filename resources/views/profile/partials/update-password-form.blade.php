<section>
    <div class="row justify-content-start">
        <div class="col-lg-12 col-md-12">
            <div class="mb-4">
                <h2 class="h5 text-primary font-weight-bold">
                    {{ __('Ubah Password') }}
                </h2>
                <p class="text-muted small mb-0">
                    {{ __('Pastikan password Anda panjang dan sulit ditebak untuk menjaga keamanan akun.') }}
                </p>
            </div>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('put')

                <!-- Password Sekarang -->
                <div class="row mb-3">
                    <label for="update_password_current_password" class="col-sm-3 col-form-label">
                        {{ __('Password Sekarang') }}
                    </label>
                    <div class="col-sm-9">
                        <input
                            type="password"
                            id="update_password_current_password"
                            name="current_password"
                            class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                            autocomplete="current-password"
                        >
                        @error('current_password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Password Baru -->
                <div class="row mb-3">
                    <label for="update_password_password" class="col-sm-3 col-form-label">
                        {{ __('Password Baru') }}
                    </label>
                    <div class="col-sm-9">
                        <input
                            type="password"
                            id="update_password_password"
                            name="password"
                            class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                            autocomplete="new-password"
                        >
                        @error('password', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Konfirmasi Password -->
                <div class="row mb-3">
                    <label for="update_password_password_confirmation" class="col-sm-3 col-form-label">
                        {{ __('Konfirmasi Password') }}
                    </label>
                    <div class="col-sm-9">
                        <input
                            type="password"
                            id="update_password_password_confirmation"
                            name="password_confirmation"
                            class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                            autocomplete="new-password"
                        >
                        @error('password_confirmation', 'updatePassword')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Tombol Simpan -->
                <div class="d-flex align-items-center mt-4">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Simpan Perubahan') }}
                    </button>

                    @if (session('status') === 'password-updated')
                        <p class="text-success small mb-0 ml-3">
                            {{ __('Password berhasil diperbarui.') }}
                        </p>
                    @endif
                </div>
            </form>
        </div>
    </div>
</section>
