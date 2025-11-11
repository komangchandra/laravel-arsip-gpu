<section>
    <div class="row justify-content-start">
        <div class="col-lg-12 col-md-12">
            <div class="mb-4">
                <h2 class="h5 text-primary font-weight-bold">
                    {{ __('Informasi Profil') }}
                </h2>
            </div>

            <!-- Form for email verification -->
            <form id="send-verification" method="POST" action="{{ route('verification.send') }}">
                @csrf
            </form>

            <!-- Profile Update Form -->
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('patch')

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label" for="name">{{ __('Name') }}</label>
                    <div class="col-sm-10">
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $user->name) }}"
                            required
                            autofocus
                            autocomplete="name"
                        >
                    </div>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <label class="col-sm-2 col-form-label" for="email">{{ __('Email') }}</label>
                    <div class="col-sm-10">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $user->email) }}"
                            required
                            autocomplete="username"
                            readonly
                        >
                    </div>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="alert alert-warning mt-3 mb-0 py-2 px-3 small">
                            <p class="mb-1">
                                {{ __('Your email address is unverified.') }}
                                <button
                                    type="submit"
                                    form="send-verification"
                                    class="btn btn-link btn-sm p-0 align-baseline"
                                >
                                    {{ __('Click here to re-send the verification email.') }}
                                </button>
                            </p>

                            @if (session('status') === 'verification-link-sent')
                                <p class="text-success mb-0">
                                    {{ __('A new verification link has been sent to your email address.') }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="d-flex align-items-center mt-4">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Simpan perubahan') }}
                    </button>

                    @if (session('status') === 'profile-updated')
                        <p class="text-success small mb-0 ml-3">
                            {{ __('Tersimpan.') }}
                        </p>
                    @endif
                </div>
            </form>
        </div>
    </div>
</section>
