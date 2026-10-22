@extends(\Parth\LaravelRbacSuite\RbacSuite::getLayout())
@section('content')
    @push('styles')
        <style>
            /* Your existing styles */
            body {
                background-color: #f5f7fb !important;
                font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
                color: #333;
            }

            .container-main {
                max-width: 1200px;
            }

            .card {
                border-radius: 12px;
                border: 1px solid #e9ecef;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                overflow: hidden;
            }

            .card:hover {
                box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
            }

            .card-header {
                background-color: white;
                border-bottom: 1px solid #e9ecef;
                padding: 1.25rem 1.5rem;
                font-weight: 600;
                font-size: 1.2rem;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .card-header i {
                color: #6f42c1;
            }

            .card-body {
                padding: 1.5rem;
            }

            .section-title {
                font-weight: 600;
                font-size: 1rem;
                margin-bottom: 15px;
                color: #444;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .section-title i {
                font-size: 0.9rem;
                color: #6f42c1;
            }

            .section-subtitle {
                font-size: 0.9rem;
                color: #6c757d;
                margin-bottom: 1.25rem;
                max-width: 800px;
            }

            .divider {
                border-top: 1px solid #e9ecef;
                margin: 1.75rem 0;
            }

            .form-check {
                margin-bottom: 0.75rem;
                padding-left: 2.5rem;
            }

            .form-check-input {
                width: 1.2rem;
                height: 1.2rem;
                margin-left: -2.5rem;
                margin-top: 0.15rem;
                cursor: pointer;
                border: 2px solid #adb5bd;
            }

            .form-check-input:checked {
                background-color: #6f42c1;
                border-color: #6f42c1;
            }

            .form-check-input:focus {
                box-shadow: 0 0 0 0.25rem rgba(111, 66, 193, 0.25);
            }

            .form-check-label {
                cursor: pointer;
                font-weight: 500;
                font-size: 0.95rem;
                margin-left: 10px;
            }

            .form-label {
                font-weight: 600;
                font-size: 0.9rem;
                margin-bottom: 0.5rem;
                color: #444;
            }

            .form-control {
                border-radius: 8px;
                border: 1px solid #e9ecef;
                padding: 0.6rem 0.9rem;
                font-size: 0.95rem;
            }

            .form-control:focus {
                border-color: #6f42c1;
                box-shadow: 0 0 0 0.25rem rgba(111, 66, 193, 0.15);
            }

            .small-note {
                font-size: 0.85rem;
                color: #6c757d;
                margin-top: 0.5rem;
                line-height: 1.5;
                display: flex;
                align-items: flex-start;
                gap: 6px;
            }

            .small-note i {
                color: #ffc107;
                font-size: 1rem;
                margin-top: 0.1rem;
            }

            .badge-info {
                background-color: var(--primary-light);
                color: #6f42c1;
                font-weight: 500;
                padding: 0.3rem 0.6rem;
                font-size: 0.75rem;
                border-radius: 4px;
                margin-left: 8px;
            }

            .input-group-text {
                background-color: #f8f9fa;
                border: 1px solid #e9ecef;
                font-size: 0.9rem;
            }

            .btn {
                border-radius: 8px;
                padding: 0.5rem 1.5rem;
                font-weight: 500;
                transition: all 0.2s ease;
            }

            .btn-primary {
                background-color: #6f42c1;
                border-color: #6f42c1;
            }

            .btn-primary:hover {
                background-color: #5e35b1;
                border-color: #5e35b1;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(111, 66, 193, 0.25);
            }

            .btn-outline-secondary {
                border-color: #e9ecef;
                color: #666;
            }

            .btn-outline-secondary:hover {
                background-color: #f8f9fa;
                color: #333;
            }

            .method-icon {
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                background-color: var(--primary-light);
                color: #6f42c1;
                margin-bottom: 10px;
            }

            .method-card {
                border: 1px solid #e9ecef;
                border-radius: 10px;
                padding: 1.25rem;
                transition: all 0.2s ease;
                height: 100%;
                cursor: pointer;
            }

            .method-card:hover {
                border-color: #6f42c1;
                background-color: rgba(111, 66, 193, 0.02);
            }

            .method-card.active {
                border-color: #6f42c1;
                background-color: rgba(111, 66, 193, 0.05);
            }

            .method-name {
                font-weight: 600;
                font-size: 0.95rem;
                margin-bottom: 0.25rem;
            }

            .method-desc {
                font-size: 0.85rem;
                color: #6c757d;
                line-height: 1.4;
            }

            .toggle-switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 26px;
                margin-bottom: 0;
            }

            .toggle-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 34px;
            }

            .toggle-slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }

            input:checked+.toggle-slider {
                background-color: #6f42c1;
            }

            input:checked+.toggle-slider:before {
                transform: translateX(24px);
            }

            .settings-group {
                background-color: #f9fafc;
                border-radius: 10px;
                padding: 1.25rem;
                margin-bottom: 1.5rem;
                border: 1px solid #e9ecef;
            }

            .status-indicator {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 0.85rem;
                padding: 0.25rem 0.75rem;
                border-radius: 50px;
                font-weight: 500;
            }

            .status-active {
                background-color: rgba(40, 167, 69, 0.1);
                color: #28a745;
            }

            .status-inactive {
                background-color: rgba(108, 117, 125, 0.1);
                color: #6c757d;
            }

            @media (max-width: 768px) {
                .container-main {
                    margin: 1rem auto;
                    padding: 0 1rem;
                }

                .card-body {
                    padding: 1.25rem;
                }

                .section-title {
                    font-size: 0.95rem;
                }

                #toast-container {
                    min-width: 90%;
                    left: 5% !important;
                    right: 5% !important;
                }
            }

            .config-section {
                animation: fadeIn 0.3s ease;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .nav {
                --bs-nav-link-color: #0d6efd;
            }

            .nav-tabs {
                --bs-nav-tabs-link-active-bg: #fff !important;
                --bs-nav-tabs-border-width: 1px;
                --bs-nav-tabs-border-color: #dee2e6;
            }

            /* Offcanvas responsive styling: 50% width on desktop, 100% on tablet/mobile */
            .offcanvas.offcanvas-auth-config,
            #btnEmailSidebar,
            #btnWhatsAppSidebar,
            #btnMobileOtpSidebar,
            #btnTwoFaSidebar,
            #btnMacSidebar {
                width: 70% !important;
                max-width: 70% !important;
            }

            @media (max-width: 991.98px) {

                .offcanvas.offcanvas-auth-config,
                #btnEmailSidebar,
                #btnWhatsAppSidebar,
                #btnMobileOtpSidebar,
                #btnTwoFaSidebar,
                #btnMacSidebar {
                    width: 100% !important;
                    max-width: 100% !important;
                }
            }
        </style>
    @endpush

    <div class="container-main">
        <ul class="nav nav-tabs mb-4" id="authTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="auth-tab" data-bs-toggle="tab" data-bs-target="#authSettings"
                    type="button" role="tab">
                    <i class="fas fa-shield-alt me-1"></i> Authentication Settings
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#passwordCriteria"
                    type="button" role="tab">
                    <i class="fas fa-solid fa-key me-1"></i> Password Criteria
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="authSettings" role="tabpanel">
                <input type="hidden" id="captchaAuthSettingId" />
                <input type="hidden" id="emailOtpAuthSettingId" />
                <input type="hidden" id="mobileOtpAuthSettingId" />
                <input type="hidden" id="whatsAppOtpAuthSettingId" />
                <input type="hidden" id="twoFaAuthSettingId" />
                <input type="hidden" id="macAuthSettingId" />
                <input type="hidden" id="signupAuthSettingId" />

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-shield-alt"></i> Authentication Methods
                    </div>

                    <div class="card-body">
                        <p class="section-subtitle">
                            Enable or disable authentication methods for your users. Multiple methods can be active
                            simultaneously.
                        </p>

                        <div class="row g-3 mb-1">
                            <div class="col-md-6 col-lg-3">
                                <div class="method-card" id="captchaCard">
                                    <div class="method-icon">
                                        <i class="fas fa-solid fa-robot" style="font-size: 1.2rem"></i>
                                    </div>
                                    <div class="method-name">Captcha</div>
                                    <div class="method-desc">Protects against automated attacks and bots</div>
                                    <div class="mt-3">
                                        <div class="form-check form-switch form-switch-lg">
                                            <input type="checkbox" class="form-check-input" id="captchaToggle">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <div class="method-card" id="emailOtpCard">
                                    <div class="method-icon">
                                        <i class="fas fa-solid fa-envelope" style="font-size: 1.2rem"></i>
                                    </div>
                                    <div class="method-name">Email OTP</div>
                                    <div class="method-desc">
                                        One-time password sent to user's registered email address
                                    </div>

                                    <div class="mt-3 d-flex justify-content-between align-items-center">
                                        <div class="form-check form-switch form-switch-lg">
                                            <input type="checkbox" class="form-check-input" id="emailOtpToggle">
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm" id="filterBtn"
                                            data-bs-toggle="offcanvas" data-bs-target="#btnEmailSidebar">
                                            <i class="fas fa-cogs"></i> configure
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <div class="method-card" id="mobileOtpCard">
                                    <div class="method-icon">
                                        <i class="fas fa-mobile-alt" style="font-size: 1.2rem"></i>
                                    </div>
                                    <div class="method-name">Mobile OTP</div>
                                    <div class="method-desc">
                                        One-time password sent via SMS to user's mobile number
                                    </div>

                                    <div class="mt-3 d-flex justify-content-between align-items-center">
                                        <div class="form-check form-switch form-switch-lg">
                                            <input type="checkbox" class="form-check-input" id="mobileOtpToggle">
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm" id="mobileOtpConfigBtn"
                                            data-bs-toggle="offcanvas" data-bs-target="#btnMobileOtpSidebar">
                                            <i class="fas fa-cogs"></i> configure
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <div class="method-card" id="twoFaCard">
                                    <div class="method-icon">
                                        <i class="fas fa-solid fa-key" style="font-size: 1.2rem"></i>
                                    </div>
                                    <div class="method-name">Two-Factor Authentication</div>
                                    <div class="method-desc">Authenticator apps like Google Authenticator or Authy</div>

                                    <div class="mt-3 d-flex justify-content-between align-items-center">
                                        <div class="form-check form-switch form-switch-lg">
                                            <input type="checkbox" class="form-check-input" id="twoFaToggle">
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm" id="twoFaConfigBtn"
                                            data-bs-toggle="offcanvas" data-bs-target="#btnTwoFaSidebar">
                                            <i class="fas fa-cogs"></i> configure
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <div class="method-card" id="whatsAppOtpCard">
                                    <div class="method-icon">
                                        <i class="fab fa-whatsapp" style="font-size: 1.2rem"></i>
                                    </div>
                                    <div class="method-name">WhatsApp OTP</div>
                                    <div class="method-desc">
                                        One-time password sent via WhatsApp to user's verified mobile number
                                    </div>

                                    <div class="mt-3 d-flex justify-content-between align-items-center">
                                        <div class="form-check form-switch form-switch-lg">
                                            <input type="checkbox" class="form-check-input" id="whatsAppOtpToggle">
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm" id="whatsappConfigBtn"
                                            data-bs-toggle="offcanvas" data-bs-target="#btnWhatsAppSidebar">
                                            <i class="fas fa-cogs"></i> configure
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <div class="method-card" id="macCard">
                                    <div class="method-icon">
                                        <i class="fas fa-network-wired" style="font-size: 1.2rem"></i>
                                    </div>
                                    <div class="method-name">MAC Functionality</div>
                                    <div class="method-desc">
                                        Allows login only from authorized devices by validating the device MAC address.
                                    </div>

                                    <div class="mt-3 d-flex justify-content-between align-items-center">
                                        <div class="form-check form-switch form-switch-lg">
                                            <input type="checkbox" class="form-check-input" id="macToggle">
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm" id="macConfigBtn"
                                            data-bs-toggle="offcanvas" data-bs-target="#btnMacSidebar">
                                            <i class="fas fa-cogs"></i> configure
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <div class="method-card" id="signupCard">
                                    <div class="method-icon">
                                        <i class="fas fa-user-plus" style="font-size: 1.2rem"></i>
                                    </div>
                                    <div class="method-name">Signup / Registration</div>
                                    <div class="method-desc">
                                        Allow new users to register and create accounts directly from the login page.
                                    </div>

                                    <div class="mt-3">
                                        <div class="form-check form-switch form-switch-lg">
                                            <input type="checkbox" class="form-check-input" id="signupToggle">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="small-note">
                            <i class="fas fa-info-circle"></i>
                            <span>
                                At least one authentication method must be active. Changes will take effect immediately.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="passwordCriteria" role="tabpanel">
                <div class="card mb-4 h-100">
                    <div class="card-header">
                        <i class="fas fa-solid fa-key"></i> Password Validation Rules
                    </div>

                    <div class="card-body">
                        <p class="section-subtitle">Define password complexity requirements for user accounts.</p>

                        <div class="settings-group">
                            <!-- Minimum Length Rule -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check g-1">
                                    <input class="form-check-input" type="checkbox" id="minLength" data-ruleid="0">
                                    <label class="form-check-label" for="minLength">Minimum 8 characters</label>
                                </div>
                                <span class="badge-info">Required</span>
                            </div>

                            <!-- Uppercase Rule -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check g-1">
                                    <input class="form-check-input" type="checkbox" id="uppercase" data-ruleid="0">
                                    <label class="form-check-label" for="uppercase">At least 1 uppercase letter</label>
                                </div>
                            </div>

                            <!-- Lowercase Rule -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check g-1">
                                    <input class="form-check-input" type="checkbox" id="lowercase" data-ruleid="0">
                                    <label class="form-check-label" for="lowercase">At least 1 lowercase letter</label>
                                </div>
                            </div>

                            <!-- Numeric Rule -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check g-1">
                                    <input class="form-check-input" type="checkbox" id="number" data-ruleid="0">
                                    <label class="form-check-label" for="number">At least 1 number</label>
                                </div>
                            </div>

                            <!-- Special Character Rule -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="specialChar" data-ruleid="0">
                                    <label class="form-check-label" for="specialChar">
                                        At least 1 special character
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="small-note mt-3">
                            <i class="fas fa-lightbulb"></i>
                            <span>
                                All selected rules will be enforced during password creation and password reset.
                            </span>
                        </div>

                        <div class="d-flex justify-content-end gap-2 p-3">
                            <button type="button" class="btn btn-outline-secondary px-4" id="btnCancelPasswordRule">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                            <button type="button" class="btn btn-primary px-4" id="updatePasswordRules">
                                <i class="fas fa-save me-1"></i> Update Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>


    </div>


    <div class="offcanvas offcanvas-end offcanvas-auth-config" tabindex="-1" id="btnEmailSidebar">
        <div class="offcanvas-header">
            <h5>Email Configuration</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body" id="btnEmailForm" data-has-existing-password="0">

            <input type="hidden" id="editIndex">

            <!-- OTP Rules & Limits -->
            <div class="settings-group mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-clock text-primary me-2"></i>OTP Rules & Limits</h6>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Email OTP Attempts</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="1" max="10"
                                id="txtEmailOtpAttempts" placeholder="5">
                            <span class="input-group-text">times</span>
                        </div>
                        <div class="text-danger small field-error d-none" id="lblEmailOtpAttemptsError"></div>
                        <div class="small-note mt-1">Maximum incorrect attempts before lockout</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Email OTP Reset</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="1" max="60"
                                id="txtEmailOtpReset" placeholder="5">
                            <span class="input-group-text">minutes</span>
                        </div>
                        <div class="text-danger small field-error d-none" id="lblEmailOtpResetError"></div>
                        <div class="small-note mt-1">Timeout for Resend OTP</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Email OTP Expiration Time</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="1" max="120"
                                id="txtEmailOtpExpiry" placeholder="10">
                            <span class="input-group-text">minutes</span>
                        </div>
                        <div class="text-danger small field-error d-none" id="lblEmailOtpExpiryError"></div>
                        <div class="small-note mt-1">Time after which the OTP becomes invalid</div>
                    </div>
                </div>
            </div>

            <!-- SMTP Settings -->
            <div class="settings-group mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-server text-primary me-2"></i>SMTP Gateway Configuration</h6>
                <div class="row g-2">
                    <div class="col-md-8 mb-2">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" id="txtSmtpHost" required
                            placeholder="e.g. smtp.mailgun.org">
                        <div class="text-danger small field-error d-none" id="lblSmtpHostError"></div>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" id="txtSmtpPort" required placeholder="587">
                        <div class="text-danger small field-error d-none" id="lblSmtpPortError"></div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" class="form-control" id="txtSmtpUsername"
                            placeholder="e.g. user@example.com">
                        <div class="text-danger small field-error d-none" id="lblSmtpUsernameError"></div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" class="form-control" id="txtSmtpPassword" autocomplete="new-password">
                        <div class="small text-muted">Leave empty to keep existing password.</div>
                        <div class="text-danger small field-error d-none" id="lblSmtpPasswordError"></div>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">SMTP Encryption</label>
                        <select class="form-control" id="ddlSmtpEncryption">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="none">None</option>
                        </select>
                        <div class="text-danger small field-error d-none" id="lblSmtpEncryptionError"></div>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">SMTP From Email</label>
                        <input type="email" class="form-control" id="txtSmtpFromEmail" required
                            placeholder="e.g. noreply@example.com">
                        <div class="text-danger small field-error d-none" id="lblSmtpFromEmailError"></div>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">SMTP From Name</label>
                        <input type="text" class="form-control" id="txtSmtpFromName"
                            placeholder="e.g. Application Name">
                        <div class="text-danger small field-error d-none" id="lblSmtpFromNameError"></div>
                    </div>
                </div>
            </div>

            <!-- Test Email -->
            <div class="settings-group mb-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-paper-plane text-primary me-2"></i>Test Email</h6>
                <div class="mb-2">
                    <label class="form-label">Test Recipient Email</label>
                    <input type="email" class="form-control" id="txtTestRecipientEmail"
                        placeholder="e.g. admin@example.com">
                    <div class="small-note mt-1">Recipient email address to test SMTP delivery.</div>
                    <div class="text-danger small field-error d-none" id="lblTestRecipientEmailError"></div>
                </div>
            </div>

            <input type="hidden" id="txtemail">
            <input type="hidden" id="txtPassword">
            <input type="hidden" id="txtSmtpServer">

            <button type="button" class="btn btn-primary" id="btnSaveEmail">Save</button>
            <button type="button" class="btn btn-info text-white ms-2" id="btnTestEmail">Test Email Send</button>
            <button type="button" class="btn btn-secondary ms-2" id="btnCancelEmail">Reset</button>

        </div>
    </div>

    <div class="offcanvas offcanvas-end offcanvas-auth-config" tabindex="-1" id="btnWhatsAppSidebar">
        <div class="offcanvas-header">
            <h5>WhatsApp Gateway Configuration</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body" id="btnWhatsAppForm" data-has-existing-token="0" data-has-existing-password="0">

            <!-- OTP Rules & Limits -->
            <div class="settings-group mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-clock text-primary me-2"></i>OTP Rules & Limits</h6>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">WhatsApp OTP Attempts</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="1" max="10"
                                id="txtWhatsAppOtpAttempts" placeholder="5">
                            <span class="input-group-text">times</span>
                        </div>
                        <div class="text-danger small field-error d-none" id="lblWhatsAppOtpAttemptsError"></div>
                        <div class="small-note mt-1">Maximum incorrect attempts before lockout</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">WhatsApp OTP Reset</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="1" max="60"
                                id="txtWhatsAppOtpReset" placeholder="5">
                            <span class="input-group-text">minutes</span>
                        </div>
                        <div class="text-danger small field-error d-none" id="lblWhatsAppOtpResetError"></div>
                        <div class="small-note mt-1">Timeout for Resend OTP</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">WhatsApp OTP Expiration Time</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="1" max="120"
                                id="txtWhatsAppOtpExpiry" placeholder="10">
                            <span class="input-group-text">minutes</span>
                        </div>
                        <div class="text-danger small field-error d-none" id="lblWhatsAppOtpExpiryError"></div>
                        <div class="small-note mt-1">Time after which the OTP becomes invalid</div>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Gateway Settings -->
            <div class="settings-group mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-server text-primary me-2"></i>Gateway Details</h6>
                <div class="row g-2">
                    <div class="col-12 mb-2">
                        <label class="form-label">Gateway URL</label>
                        <input type="url" class="form-control" id="txtWhatsAppUrl" required>
                        <div class="text-danger small field-error d-none" id="lblWhatsAppUrlError"></div>
                    </div>

                    <div class="col-12 mb-2">
                        <label class="form-label">Bearer Token</label>
                        <input type="password" class="form-control" id="txtWhatsAppToken" autocomplete="new-password">
                        <div class="small text-muted">Leave empty to keep existing token.</div>
                        <div class="text-danger small field-error d-none" id="lblWhatsAppTokenError"></div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="txtWhatsAppUsername">
                        <div class="text-danger small field-error d-none" id="lblWhatsAppUsernameError"></div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" id="txtWhatsAppPassword"
                            autocomplete="new-password">
                        <div class="small text-muted">Leave empty to keep existing password.</div>
                        <div class="text-danger small field-error d-none" id="lblWhatsAppPasswordError"></div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Sender ID</label>
                        <input type="text" class="form-control" id="txtWhatsAppSenderId" required>
                        <div class="text-danger small field-error d-none" id="lblWhatsAppSenderIdError"></div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Template</label>
                        <input type="text" class="form-control" id="txtWhatsAppTemplate" required>
                        <div class="text-danger small field-error d-none" id="lblWhatsAppTemplateError"></div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Timeout (seconds)</label>
                        <input type="number" class="form-control" id="txtWhatsAppTimeout" min="1"
                            max="120" required>
                        <div class="text-danger small field-error d-none" id="lblWhatsAppTimeoutError"></div>
                    </div>

                    <div class="col-md-6 mb-2 d-flex align-items-center mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="chkWhatsAppVerifySsl">
                            <label class="form-check-label" for="chkWhatsAppVerifySsl">
                                Verify SSL certificate
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-primary" id="btnSaveWhatsApp">Save</button>
            <button type="button" class="btn btn-secondary ms-2" id="btnCancelWhatsApp">Reset</button>

        </div>
    </div>

    <div class="offcanvas offcanvas-end offcanvas-auth-config" tabindex="-1" id="btnMobileOtpSidebar">
        <div class="offcanvas-header">
            <h5>Mobile OTP SMS Configuration</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body" id="btnMobileOtpForm" data-has-existing-token="0" data-has-existing-password="0">

            <!-- OTP Rules & Limits -->
            <div class="settings-group mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-clock text-primary me-2"></i>OTP Rules & Limits</h6>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Mobile OTP Attempts</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="1" max="10"
                                id="txtMobileOtpAttempts" placeholder="5">
                            <span class="input-group-text">times</span>
                        </div>
                        <div class="text-danger small field-error d-none" id="lblMobileOtpAttemptsError"></div>
                        <div class="small-note mt-1">Maximum incorrect attempts before lockout</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Mobile OTP Reset</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="1" max="60"
                                id="txtMobileOtpReset" placeholder="5">
                            <span class="input-group-text">minutes</span>
                        </div>
                        <div class="text-danger small field-error d-none" id="lblMobileOtpResetError"></div>
                        <div class="small-note mt-1">Timeout for Resend OTP</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Mobile OTP Expiration Time</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="1" max="120"
                                id="txtMobileOtpExpiry" placeholder="10">
                            <span class="input-group-text">minutes</span>
                        </div>
                        <div class="text-danger small field-error d-none" id="lblMobileOtpExpiryError"></div>
                        <div class="small-note mt-1">Time after which the OTP becomes invalid</div>
                    </div>
                </div>
            </div>

            <!-- SMS Gateway Settings -->
            <div class="settings-group mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-server text-primary me-2"></i>SMS Gateway Configuration</h6>
                <div class="row g-2">
                    <div class="col-12 mb-2">
                        <label class="form-label">Gateway URL</label>
                        <input type="url" class="form-control" id="txtMobileOtpUrl" required
                            placeholder="https://sms-gateway.example.com/send">
                        <div class="text-danger small field-error d-none" id="lblMobileOtpUrlError"></div>
                    </div>

                    <div class="col-12 mb-2">
                        <label class="form-label">Bearer Token</label>
                        <input type="password" class="form-control" id="txtMobileOtpToken" autocomplete="new-password">
                        <div class="small text-muted">Leave empty to keep existing token. Provide either token or
                            username/password.</div>
                        <div class="text-danger small field-error d-none" id="lblMobileOtpTokenError"></div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="txtMobileOtpUsername"
                            placeholder="SMS Gateway Username">
                        <div class="text-danger small field-error d-none" id="lblMobileOtpUsernameError"></div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" id="txtMobileOtpPassword"
                            autocomplete="new-password">
                        <div class="small text-muted">Leave empty to keep existing password.</div>
                        <div class="text-danger small field-error d-none" id="lblMobileOtpPasswordError"></div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Sender ID</label>
                        <input type="text" class="form-control" id="txtMobileOtpSenderId" placeholder="e.g. MYAPP">
                        <div class="text-danger small field-error d-none" id="lblMobileOtpSenderIdError"></div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Template / DLT ID</label>
                        <input type="text" class="form-control" id="txtMobileOtpTemplate"
                            placeholder="e.g. otp_template or DLT ID (optional)">
                        <div class="text-danger small field-error d-none" id="lblMobileOtpTemplateError"></div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Timeout (seconds)</label>
                        <input type="number" class="form-control" id="txtMobileOtpTimeout" min="1"
                            max="120" value="10" required>
                        <div class="text-danger small field-error d-none" id="lblMobileOtpTimeoutError"></div>
                    </div>

                    <div class="col-md-6 mb-2 d-flex align-items-center mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="chkMobileOtpVerifySsl" checked>
                            <label class="form-check-label" for="chkMobileOtpVerifySsl">
                                Verify SSL certificate
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-primary" id="btnSaveMobileOtp">Save</button>
            <button type="button" class="btn btn-secondary ms-2" id="btnCancelMobileOtp">Reset</button>

        </div>
    </div>

    <div class="offcanvas offcanvas-end offcanvas-auth-config" tabindex="-1" id="btnTwoFaSidebar">
        <div class="offcanvas-header">
            <h5>Two-Factor Configuration</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body" id="btnTwoFaForm">
            <div class="settings-group mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-shield-alt text-primary me-2"></i>User Status</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary">
                        Total Users: <span id="twoFaTotalUsers">0</span>
                    </span>
                    <span class="badge bg-success-subtle text-success">
                        Configured Users: <span id="twoFaConfiguredUsers">0</span>
                    </span>
                    <span class="badge bg-info-subtle text-info">
                        Active Rows: <span id="twoFaActiveRows">0</span>
                    </span>
                </div>
            </div>

            <div class="settings-group mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-clock text-primary me-2"></i>2FA Verification Rules</h6>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">2FA Attempts</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="1" max="10"
                                id="twoFaAttemptsConfig" placeholder="5">
                            <span class="input-group-text">times</span>
                        </div>
                        <div class="text-danger small field-error d-none" id="lblTwoFaAttemptsError"></div>
                        <div class="small-note mt-1">Maximum incorrect attempts before lockout</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">2FA Expiration Time</label>
                        <div class="input-group">
                            <input type="number" class="form-control" min="1" max="120"
                                id="twoFaExpiryConfig" placeholder="10">
                            <span class="input-group-text">minutes</span>
                        </div>
                        <div class="text-danger small field-error d-none" id="lblTwoFaExpiryError"></div>
                        <div class="small-note mt-1">Time window allowed for completing verification</div>
                    </div>
                </div>
            </div>

            <div class="settings-group mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-key text-primary me-2"></i>Authenticator Settings</h6>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="google2faEnabledConfig">
                    <label class="form-check-label" for="google2faEnabledConfig">Google2FA Enabled</label>
                </div>
                <div class="small-note mt-1">Enable or disable Google2FA for the selected 2FA profile row.</div>
            </div>

            <button type="button" class="btn btn-primary" id="btnSaveTwoFaConfig">Save</button>
            <button type="button" class="btn btn-secondary ms-2" id="btnCancelTwoFaConfig">Reset</button>
        </div>
    </div>

    <div class="offcanvas offcanvas-end offcanvas-auth-config" tabindex="-1" id="btnMacSidebar">
        <div class="offcanvas-header">
            <h5 class="fw-bold"><i class="fas fa-network-wired text-primary me-2"></i>MAC Functionality Configuration</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body pb-5" id="btnMacForm">
            <!-- Summary Badges -->
            <div class="settings-group mb-3">
                <h6 class="fw-bold mb-2"><i class="fas fa-users text-primary me-2"></i>User Device Overview</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                        Total Users: <span id="macTotalUsers" class="fw-bold">0</span>
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        Configured: <span id="macConfiguredUsers" class="fw-bold">0</span>
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                        Not Configured: <span id="macUnconfiguredUsers" class="fw-bold">0</span>
                    </span>
                </div>
            </div>

            <!-- Search Bar and Refresh -->
            <div class="settings-group mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control" id="txtMacUserSearch"
                                placeholder="Search by name, email, or mobile...">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary" id="btnRefreshMacUsers"
                            title="Refresh user list">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Registered Users Section -->
            <div class="settings-group mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <h6 class="fw-bold mb-0"><i class="fas fa-list text-primary me-2"></i>Registered Users</h6>
                    <div class="btn-group btn-group-sm" role="group" id="macFilterButtonGroup">
                        <button type="button" class="btn btn-outline-primary active" data-filter="all">All</button>
                        <button type="button" class="btn btn-outline-primary"
                            data-filter="configured">Configured</button>
                        <button type="button" class="btn btn-outline-primary" data-filter="unconfigured">Not
                            Configured</button>
                    </div>
                </div>

                <!-- Loading Spinner -->
                <div id="macUsersLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 text-muted small">Loading user list...</div>
                </div>

                <!-- Empty Search Results -->
                <div id="macUsersEmpty" class="text-center py-4 d-none">
                    <i class="fas fa-user-slash text-muted fa-2x mb-2"></i>
                    <div class="text-muted small">No users match your criteria.</div>
                </div>

                <!-- User Cards Container -->
                <div id="macUsersContainer" class="d-flex flex-column gap-2 mb-4">
                    <!-- Populated dynamically via JavaScript -->
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <!-- apexcharts -->
    <script src="{{ asset(config('rbac-suite.assets_prefix', 'vendor/rbac-suite') . '/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset(config('rbac-suite.assets_prefix', 'vendor/rbac-suite') . '/js/pages/dashboard.init.js') }}"></script>


    <script>
        // Add CSRF token to all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function() {
            let isInitialLoading = true;

            // Prevent button click from toggling checkbox
            $('.method-card button').on('click', function(e) {
                e.stopPropagation();
            });

            loadAuthSettings();
            loadEmailConfig();
            loadWhatsAppConfig();
            loadMobileOtpConfig();
            loadPasswordRules();

            function loadAuthSettings() {
                $.ajax({
                    url: '{{ route('getAuthenticationSettings') }}',
                    type: 'GET',
                    success: function(data) {
                        isInitialLoading = true;
                        bindAuthSettings(data);
                        isInitialLoading = false;
                    },
                    error: function(xhr, status, error) {
                        isInitialLoading = false;
                        console.error('Error loading auth settings:', error);
                        notificationToastCustom('error', 'Failed to load authentication settings');
                    }
                });
            }

            function bindAuthSettings(settings) {
                settings.forEach(function(item) {
                    switch (item.authCode) {
                        case 'CAPTCHA':
                            $('#captchaAuthSettingId').val(item.authSettingId);
                            $('#captchaToggle').prop('checked', !!item.isEnabled);
                            $('#captchaToggle').closest('.method-card').toggleClass('active', !!item
                                .isEnabled);
                            break;

                        case 'EMAIL_VERIFY':
                            $('#emailOtpAuthSettingId').val(item.authSettingId);
                            $('#emailOtpToggle').prop('checked', !!item.isEnabled);
                            $('#emailOtpToggle').closest('.method-card').toggleClass('active', !!item
                                .isEnabled);

                            if (item.otpattempt) {
                                $('#txtEmailOtpAttempts').val(item.otpattempt);
                                $('#txtEmailOtpAttempts').data('savedValue', item.otpattempt);
                            }
                            if (item.otpresetTime) {
                                $('#txtEmailOtpReset').val(item.otpresetTime);
                                $('#txtEmailOtpReset').data('savedValue', item.otpresetTime);
                            }
                            if (item.otpexpiryTime != null) {
                                $('#txtEmailOtpExpiry').val(item.otpexpiryTime);
                                $('#txtEmailOtpExpiry').data('savedValue', item.otpexpiryTime);
                            }
                            break;

                        case 'MOBILE_VERIFY':
                            $('#mobileOtpAuthSettingId').val(item.authSettingId);
                            $('#mobileOtpToggle').prop('checked', !!item.isEnabled);
                            $('#mobileOtpToggle').closest('.method-card').toggleClass('active', !!item
                                .isEnabled);

                            if (item.otpattempt) {
                                $('#txtMobileOtpAttempts').val(item.otpattempt);
                                $('#txtMobileOtpAttempts').data('savedValue', item.otpattempt);
                            }
                            if (item.otpresetTime) {
                                $('#txtMobileOtpReset').val(item.otpresetTime);
                                $('#txtMobileOtpReset').data('savedValue', item.otpresetTime);
                            }
                            if (item.otpexpiryTime != null) {
                                $('#txtMobileOtpExpiry').val(item.otpexpiryTime);
                                $('#txtMobileOtpExpiry').data('savedValue', item.otpexpiryTime);
                            }
                            break;

                        case 'WHATSAPP_VERIFY':
                            $('#whatsAppOtpAuthSettingId').val(item.authSettingId);
                            $('#whatsAppOtpToggle').prop('checked', !!item.isEnabled);
                            $('#whatsAppOtpToggle').closest('.method-card').toggleClass('active', !!item
                                .isEnabled);

                            if (item.otpattempt) {
                                $('#txtWhatsAppOtpAttempts').val(item.otpattempt);
                                $('#txtWhatsAppOtpAttempts').data('savedValue', item.otpattempt);
                            }
                            if (item.otpresetTime) {
                                $('#txtWhatsAppOtpReset').val(item.otpresetTime);
                                $('#txtWhatsAppOtpReset').data('savedValue', item.otpresetTime);
                            }
                            if (item.otpexpiryTime != null) {
                                $('#txtWhatsAppOtpExpiry').val(item.otpexpiryTime);
                                $('#txtWhatsAppOtpExpiry').data('savedValue', item.otpexpiryTime);
                            }
                            break;

                        case 'TWO_FACTOR':
                            $('#twoFaAuthSettingId').val(item.authSettingId);
                            $('#twoFaToggle').prop('checked', !!item.isEnabled);
                            $('#twoFaToggle').closest('.method-card').toggleClass('active', !!item
                                .isEnabled);

                            if (item.otpattempt) {
                                $('#twoFaAttemptsConfig').val(item.otpattempt);
                                $('#twoFaAttemptsConfig').data('savedValue', item.otpattempt);
                            }
                            if (item.otpexpiryTime != null) {
                                $('#twoFaExpiryConfig').val(item.otpexpiryTime);
                                $('#twoFaExpiryConfig').data('savedValue', item.otpexpiryTime);
                            }
                            break;

                        case 'MAC_FUNCTIONALITY':
                            $('#macAuthSettingId').val(item.authSettingId);
                            $('#macToggle').prop('checked', !!item.isEnabled);
                            $('#macToggle').closest('.method-card').toggleClass('active', !!item.isEnabled);
                            break;

                        case 'SIGNUP_REGISTRATION':
                            $('#signupAuthSettingId').val(item.authSettingId);
                            $('#signupToggle').prop('checked', !!item.isEnabled);
                            $('#signupToggle').closest('.method-card').toggleClass('active', !!item
                                .isEnabled);
                            break;
                    }
                });
            }

            function clearTwoFaConfigErrors() {
                $('#lblTwoFaAttemptsError, #lblTwoFaExpiryError').addClass('d-none').text(
                    '');
            }

            let savedEmailConfigSnapshot = null;

            function clearEmailErrors() {
                $('#lblEmailOtpAttemptsError, #lblEmailOtpResetError, #lblEmailOtpExpiryError, #lblSmtpHostError, #lblSmtpPortError, #lblSmtpUsernameError, #lblSmtpPasswordError, #lblSmtpEncryptionError, #lblSmtpFromEmailError, #lblSmtpFromNameError, #lblTestRecipientEmailError')
                    .addClass('d-none')
                    .text('');
            }

            function getEmailFormSnapshot() {
                return {
                    host: $('#txtSmtpHost').val() ? $('#txtSmtpHost').val().trim() : '',
                    port: $('#txtSmtpPort').val() ? parseInt($('#txtSmtpPort').val(), 10) : null,
                    username: $('#txtSmtpUsername').val() ? $('#txtSmtpUsername').val().trim() : '',
                    encryption: $('#ddlSmtpEncryption').val() ? $('#ddlSmtpEncryption').val().toLowerCase() :
                        'none',
                    from_address: $('#txtSmtpFromEmail').val() ? $('#txtSmtpFromEmail').val().trim() : '',
                    from_name: $('#txtSmtpFromName').val() ? $('#txtSmtpFromName').val().trim() : '',
                    has_typed_password: ($('#txtSmtpPassword').val() && $('#txtSmtpPassword').val().length > 0) ?
                        true : false
                };
            }

            function hasUnsavedEmailChanges() {
                if (!savedEmailConfigSnapshot || !savedEmailConfigSnapshot.is_configured) {
                    return false;
                }
                const current = getEmailFormSnapshot();
                if (current.host !== (savedEmailConfigSnapshot.host || '')) return true;
                if ((current.port || null) !== (savedEmailConfigSnapshot.port || null)) return true;
                if (current.username !== (savedEmailConfigSnapshot.username || '')) return true;
                if (current.encryption !== (savedEmailConfigSnapshot.encryption || 'tls').toLowerCase())
                    return true;
                if (current.from_address !== (savedEmailConfigSnapshot.from_address || '')) return true;
                if (current.from_name !== (savedEmailConfigSnapshot.from_name || '')) return true;
                if (current.has_typed_password) return true;
                return false;
            }

            function loadEmailConfig() {
                $.ajax({
                    url: '{{ route('getSmtpSettings') }}',
                    type: 'GET',
                    success: function(data) {
                        $('#txtSmtpHost').val(data.host || '');
                        $('#txtSmtpServer').val(data.host || '');
                        $('#txtSmtpPort').val(data.port || 587);
                        $('#txtSmtpUsername').val(data.username || '');
                        $('#txtemail').val(data.username || data.from_address || '');
                        $('#txtSmtpPassword').val('');
                        $('#txtPassword').val('');
                        $('#ddlSmtpEncryption').val((data.encryption || 'tls').toLowerCase());
                        $('#txtSmtpFromEmail').val(data.from_address || '');
                        $('#txtSmtpFromName').val(data.from_name || '');

                        $('#txtSmtpPassword').attr('placeholder', data.has_password ?
                            '•••••••• (saved)' : '');
                        $('#txtPassword').attr('placeholder', data.has_password ? '•••••••• (saved)' :
                            '');

                        $('#btnEmailForm')
                            .attr('data-has-existing-password', data.has_password ? '1' : '0');

                        if (data.otpattempt) {
                            $('#txtEmailOtpAttempts').val(data.otpattempt);
                            $('#txtEmailOtpAttempts').data('savedValue', data.otpattempt);
                        }
                        if (data.otpresetTime) {
                            $('#txtEmailOtpReset').val(data.otpresetTime);
                            $('#txtEmailOtpReset').data('savedValue', data.otpresetTime);
                        }
                        if (data.otpexpiryTime != null) {
                            $('#txtEmailOtpExpiry').val(data.otpexpiryTime);
                            $('#txtEmailOtpExpiry').data('savedValue', data.otpexpiryTime);
                        }

                        savedEmailConfigSnapshot = {
                            is_configured: !!(data.host && data.port && data.from_address),
                            host: data.host || '',
                            port: data.port ? parseInt(data.port, 10) : null,
                            username: data.username || '',
                            encryption: (data.encryption || 'tls').toLowerCase(),
                            from_address: data.from_address || '',
                            from_name: data.from_name || '',
                            has_password: !!data.has_password
                        };
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading SMTP settings:', error);
                        notificationToastCustom('error', 'Failed to load SMTP configuration');
                    }
                });
            }

            function clearWhatsAppErrors() {
                $('#lblWhatsAppOtpAttemptsError, #lblWhatsAppOtpResetError, #lblWhatsAppOtpExpiryError, #lblWhatsAppUrlError, #lblWhatsAppTokenError, #lblWhatsAppUsernameError, #lblWhatsAppPasswordError, #lblWhatsAppSenderIdError, #lblWhatsAppTemplateError, #lblWhatsAppTimeoutError')
                    .addClass('d-none')
                    .text('');
            }

            function loadWhatsAppConfig() {
                $.ajax({
                    url: '{{ route('getWhatsAppGatewaySettings') }}',
                    type: 'GET',
                    success: function(data) {
                        $('#txtWhatsAppUrl').val(data.url || '');
                        $('#txtWhatsAppUsername').val(data.username || '');
                        $('#txtWhatsAppSenderId').val(data.sender_id || '');
                        $('#txtWhatsAppTemplate').val(data.template || '');
                        $('#txtWhatsAppTimeout').val(data.timeout || 10);
                        $('#chkWhatsAppVerifySsl').prop('checked', !!data.verify_ssl);

                        $('#txtWhatsAppToken').val('');
                        $('#txtWhatsAppPassword').val('');

                        $('#txtWhatsAppToken').attr('placeholder', data.has_token ? '•••••• (saved)' :
                            '');
                        $('#txtWhatsAppPassword').attr('placeholder', data.has_password ?
                            '•••••• (saved)' : '');

                        $('#btnWhatsAppForm')
                            .attr('data-has-existing-token', data.has_token ? '1' : '0')
                            .attr('data-has-existing-password', data.has_password ? '1' : '0');

                        if (data.otpattempt) {
                            $('#txtWhatsAppOtpAttempts').val(data.otpattempt);
                            $('#txtWhatsAppOtpAttempts').data('savedValue', data.otpattempt);
                        }
                        if (data.otpresetTime) {
                            $('#txtWhatsAppOtpReset').val(data.otpresetTime);
                            $('#txtWhatsAppOtpReset').data('savedValue', data.otpresetTime);
                        }
                        if (data.otpexpiryTime != null) {
                            $('#txtWhatsAppOtpExpiry').val(data.otpexpiryTime);
                            $('#txtWhatsAppOtpExpiry').data('savedValue', data.otpexpiryTime);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading WhatsApp settings:', error);
                        notificationToastCustom('error', 'Failed to load WhatsApp gateway settings');
                    }
                });
            }

            function clearMobileOtpErrors() {
                $('#lblMobileOtpAttemptsError, #lblMobileOtpResetError, #lblMobileOtpExpiryError, #lblMobileOtpUrlError, #lblMobileOtpTokenError, #lblMobileOtpUsernameError, #lblMobileOtpPasswordError, #lblMobileOtpSenderIdError, #lblMobileOtpTemplateError, #lblMobileOtpTimeoutError')
                    .addClass('d-none')
                    .text('');
            }

            function loadMobileOtpConfig() {
                $.ajax({
                    url: '{{ route('getMobileOtpGatewaySettings') }}',
                    type: 'GET',
                    success: function(data) {
                        $('#txtMobileOtpUrl').val(data.url || '');
                        $('#txtMobileOtpUsername').val(data.username || '');
                        $('#txtMobileOtpSenderId').val(data.sender_id || '');
                        $('#txtMobileOtpTemplate').val(data.template || '');
                        $('#txtMobileOtpTimeout').val(data.timeout || 10);
                        $('#chkMobileOtpVerifySsl').prop('checked', data.verify_ssl !== false);

                        $('#txtMobileOtpToken').val('');
                        $('#txtMobileOtpPassword').val('');

                        $('#txtMobileOtpToken').attr('placeholder', data.has_token ? '•••••• (saved)' :
                            '');
                        $('#txtMobileOtpPassword').attr('placeholder', data.has_password ?
                            '•••••• (saved)' : '');

                        $('#btnMobileOtpForm')
                            .attr('data-has-existing-token', data.has_token ? '1' : '0')
                            .attr('data-has-existing-password', data.has_password ? '1' : '0');

                        if (data.otpattempt) {
                            $('#txtMobileOtpAttempts').val(data.otpattempt);
                            $('#txtMobileOtpAttempts').data('savedValue', data.otpattempt);
                        }
                        if (data.otpresetTime) {
                            $('#txtMobileOtpReset').val(data.otpresetTime);
                            $('#txtMobileOtpReset').data('savedValue', data.otpresetTime);
                        }
                        if (data.otpexpiryTime != null) {
                            $('#txtMobileOtpExpiry').val(data.otpexpiryTime);
                            $('#txtMobileOtpExpiry').data('savedValue', data.otpexpiryTime);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading Mobile OTP SMS settings:', error);
                        notificationToastCustom('error', 'Failed to load Mobile OTP configuration');
                    }
                });
            }

            function loadTwoFaConfig() {
                $.ajax({
                    url: '{{ route('getTwoFactorSettings') }}',
                    type: 'GET',
                    success: function(data) {
                        if (data.authSettingId) {
                            $('#twoFaAuthSettingId').val(data.authSettingId);
                        }

                        $('#twoFaTotalUsers').text(data.totalUsers ?? 0);
                        $('#twoFaConfiguredUsers').text(data.configuredUsers ?? 0);
                        $('#twoFaActiveRows').text(data.activeRows ?? 0);

                        $('#twoFaToggle').prop('checked', !!data.isEnabled);
                        $('#twoFaToggle').closest('.method-card').toggleClass('active', !!data
                            .isEnabled);

                        $('#twoFaAttemptsConfig').val(data.otpattempt || 5);
                        $('#twoFaAttemptsConfig').data('savedValue', data.otpattempt || 5);

                        $('#twoFaExpiryConfig').val(data.otpexpiryTime || 10);
                        $('#twoFaExpiryConfig').data('savedValue', data.otpexpiryTime || 10);

                        $('#google2faEnabledConfig').prop('checked', !!data.google2fa_enabled);
                        $('#google2faEnabledConfig').data('savedValue', !!data.google2fa_enabled);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading two-factor settings:', error);
                        notificationToastCustom('error', 'Failed to load two-factor settings');
                    }
                });
            }

            /* ===============================
               Method card click interaction
            ================================ */
            $('.method-card').each(function() {
                const $card = $(this);
                const $toggle = $card.find('input[type="checkbox"]');

                $card.on('click', function(e) {
                    const ignoreSelector =
                        'input, button, a, [data-bs-toggle="offcanvas"], .btn, .btn *';
                    if ($(e.target).closest(ignoreSelector).length > 0) {
                        return;
                    }

                    $toggle.prop('checked', !$toggle.prop('checked')).trigger('change');
                });
            });

            /* ===============================
               Toggle method card & auto-save
            ================================ */
            $('.method-card input[type="checkbox"]').on('change', function() {
                const $checkbox = $(this);
                const $card = $checkbox.closest('.method-card');
                const isChecked = $checkbox.is(':checked');
                $card.toggleClass('active', isChecked);

                if (isInitialLoading) {
                    return;
                }

                const id = $checkbox.attr('id');
                let authCode = null;
                let authSettingId = null;

                if (id === 'captchaToggle') {
                    authCode = 'CAPTCHA';
                    authSettingId = $('#captchaAuthSettingId').val();
                } else if (id === 'emailOtpToggle') {
                    authCode = 'EMAIL_VERIFY';
                    authSettingId = $('#emailOtpAuthSettingId').val();
                } else if (id === 'mobileOtpToggle') {
                    authCode = 'MOBILE_VERIFY';
                    authSettingId = $('#mobileOtpAuthSettingId').val();
                } else if (id === 'whatsAppOtpToggle') {
                    authCode = 'WHATSAPP_VERIFY';
                    authSettingId = $('#whatsAppOtpAuthSettingId').val();
                } else if (id === 'twoFaToggle') {
                    authCode = 'TWO_FACTOR';
                    authSettingId = $('#twoFaAuthSettingId').val();
                } else if (id === 'macToggle') {
                    authCode = 'MAC_FUNCTIONALITY';
                    authSettingId = $('#macAuthSettingId').val();
                } else if (id === 'signupToggle') {
                    authCode = 'SIGNUP_REGISTRATION';
                    authSettingId = $('#signupAuthSettingId').val();
                }

                if (!authCode) {
                    return;
                }

                const loginToggles = ['#captchaToggle', '#emailOtpToggle', '#mobileOtpToggle',
                    '#whatsAppOtpToggle', '#twoFaToggle', '#macToggle'
                ];
                if (loginToggles.includes('#' + id) && !isChecked) {
                    const anyOtherChecked = loginToggles.some(t => t !== ('#' + id) && $(t).is(':checked'));
                    if (!anyOtherChecked) {
                        $checkbox.prop('checked', true);
                        $card.addClass('active');
                        notificationToastCustom('warning',
                            'At least one authentication method must be enabled.', 'Validation');
                        return;
                    }
                }

                const payload = [{
                    AuthSettingId: authSettingId ? parseInt(authSettingId) : null,
                    AuthCode: authCode,
                    IsEnabled: isChecked
                }];

                $.ajax({
                    url: '{{ route('updateAuthenticationSettings') }}',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(payload),
                    success: function(response) {
                        if (response.result === true) {
                            notificationToastCustom('success', response.message ||
                                'Authentication setting updated.', 'Success');
                        } else {
                            $checkbox.prop('checked', !isChecked);
                            $card.toggleClass('active', !isChecked);
                            notificationToastCustom('error', response.message ||
                                'Failed to update setting.', 'Error');
                        }
                    },
                    error: function(xhr, status, error) {
                        $checkbox.prop('checked', !isChecked);
                        $card.toggleClass('active', !isChecked);

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON
                            .message) {
                            notificationToastCustom('error', xhr.responseJSON.message,
                                'Validation Error');
                        } else if (xhr.status === 419) {
                            notificationToastCustom('error',
                                'Session expired. Please refresh the page.', 'CSRF Error');
                        } else {
                            notificationToastCustom('error',
                                'Failed to update authentication setting');
                        }
                    }
                });
            });

            $('#btnCancelPasswordRule').on('click', function(e) {
                e.preventDefault();
                loadPasswordRules();
                notificationToastCustom('info', "Changes cancelled", "Information");
            });

            $('#password-tab, [data-bs-target="#passwordCriteria"]').on('shown.bs.tab', function() {
                loadPasswordRules();
            });

            $('#filterBtn, #whatsappConfigBtn, #twoFaConfigBtn, #mobileOtpConfigBtn').on('click', function(e) {
                e.stopPropagation();
            });

            /* ===============================
               Two-Factor Sidebar
            ================================ */
            $('#twoFaConfigBtn').on('click', function() {
                clearTwoFaConfigErrors();
                loadTwoFaConfig();
            });

            $('#btnTwoFaSidebar').on('show.bs.offcanvas', function() {
                clearTwoFaConfigErrors();
                loadTwoFaConfig();
            });

            $('#btnCancelTwoFaConfig').on('click', function() {
                clearTwoFaConfigErrors();
                $('#twoFaAttemptsConfig').val($('#twoFaAttemptsConfig').data('savedValue') || '');
                $('#twoFaExpiryConfig').val($('#twoFaExpiryConfig').data('savedValue') || '');
                $('#google2faEnabledConfig').prop('checked', $('#google2faEnabledConfig').data(
                    'savedValue') === true);
                notificationToastCustom('info', '2FA settings reset.', 'Information');
            });

            $('#btnSaveTwoFaConfig').on('click', function() {
                clearTwoFaConfigErrors();

                const attempts = $('#twoFaAttemptsConfig').val() ? parseInt($('#twoFaAttemptsConfig')
                    .val()) : null;
                const expiry = $('#twoFaExpiryConfig').val() ? parseInt($('#twoFaExpiryConfig').val()) :
                    null;
                const google2faEnabled = $('#google2faEnabledConfig').is(':checked');
                const authSettingId = $('#twoFaAuthSettingId').val();
                const isEnabled = $('#twoFaToggle').is(':checked');
                let hasError = false;

                if (attempts === null || Number.isNaN(attempts) || attempts < 1 || attempts > 10) {
                    $('#lblTwoFaAttemptsError').removeClass('d-none').text(
                        '2FA attempts must be between 1 and 10.');
                    hasError = true;
                }

                if (isEnabled && (expiry === null || Number.isNaN(expiry) || expiry < 1 || expiry > 120)) {
                    $('#lblTwoFaExpiryError').removeClass('d-none').text(
                        '2FA expiration must be between 1 and 120 minutes.');
                    hasError = true;
                }

                if (!authSettingId) {
                    notificationToastCustom('error',
                        'Unable to resolve 2FA setting. Please refresh the page.', 'Error');
                    return;
                }

                if (hasError) {
                    return;
                }

                $.ajax({
                    url: '{{ route('updateTwoFactorSettings') }}',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        AuthSettingId: parseInt(authSettingId),
                        IsEnabled: isEnabled,
                        Otpattempt: attempts,
                        OtpexpiryTime: expiry,
                        google2fa_enabled: google2faEnabled
                    }),
                    success: function(response) {
                        if (response.result === true) {
                            $('#twoFaAttemptsConfig').data('savedValue', attempts);
                            $('#twoFaExpiryConfig').data('savedValue', expiry);
                            $('#google2faEnabledConfig').data('savedValue', google2faEnabled);
                            notificationToastCustom('success', response.message, 'Success');
                            loadAuthSettings();
                            loadTwoFaConfig();

                            if (window.bootstrap && bootstrap.Offcanvas) {
                                const sidebar = document.getElementById('btnTwoFaSidebar');
                                const offcanvasInstance = bootstrap.Offcanvas.getInstance(
                                    sidebar);
                                if (offcanvasInstance) {
                                    offcanvasInstance.hide();
                                }
                            }
                        } else {
                            notificationToastCustom('error', response.message ||
                                'Unable to save 2FA settings.', 'Error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Two-factor settings save error:', error);

                        if (xhr.status === 422 && xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                notificationToastCustom('error', xhr.responseJSON.message,
                                    'Validation');
                            } else if (xhr.responseJSON.errors) {
                                const errors = xhr.responseJSON.errors;
                                if (errors.Otpattempt) $('#lblTwoFaAttemptsError').removeClass(
                                    'd-none').text(errors.Otpattempt[0]);
                                if (errors.OtpexpiryTime) $('#lblTwoFaExpiryError').removeClass(
                                    'd-none').text(errors.OtpexpiryTime[0]);
                            }
                            return;
                        }

                        if (xhr.status === 419) {
                            notificationToastCustom('error',
                                'Session expired. Please refresh the page.', 'CSRF Error');
                            return;
                        }

                        notificationToastCustom('error', 'Failed to save 2FA settings',
                            'Error');
                    }
                });
            });

            /* ===============================
               Email Sidebar
            ================================ */
            $('#filterBtn').on('click', function() {
                clearEmailErrors();
                loadEmailConfig();
            });

            $('#btnEmailSidebar').on('show.bs.offcanvas', function() {
                clearEmailErrors();
                loadEmailConfig();
            });

            $('#btnCancelEmail').on('click', function() {
                clearEmailErrors();
                $('#txtEmailOtpAttempts').val($('#txtEmailOtpAttempts').data('savedValue') || '');
                $('#txtEmailOtpReset').val($('#txtEmailOtpReset').data('savedValue') || '');
                $('#txtEmailOtpExpiry').val($('#txtEmailOtpExpiry').data('savedValue') || '');
                $('#txtTestRecipientEmail').val('');
                loadEmailConfig();
                notificationToastCustom('info', 'SMTP settings reset.', 'Information');
            });

            $('#btnSaveEmail').on('click', function() {
                clearEmailErrors();
                let hasError = false;

                const attempts = $('#txtEmailOtpAttempts').val() ? parseInt($('#txtEmailOtpAttempts').val(),
                    10) : null;
                const reset = $('#txtEmailOtpReset').val() ? parseInt($('#txtEmailOtpReset').val(), 10) :
                    null;
                const expiry = $('#txtEmailOtpExpiry').val() ? parseInt($('#txtEmailOtpExpiry').val(), 10) :
                    null;

                if (attempts !== null && (attempts < 1 || attempts > 10)) {
                    $('#lblEmailOtpAttemptsError').removeClass('d-none').text(
                        'Attempts must be between 1 and 10.');
                    hasError = true;
                }
                if (reset !== null && (reset < 1 || reset > 60)) {
                    $('#lblEmailOtpResetError').removeClass('d-none').text(
                        'Reset time must be between 1 and 60 minutes.');
                    hasError = true;
                }
                if (expiry !== null && (expiry < 1 || expiry > 120)) {
                    $('#lblEmailOtpExpiryError').removeClass('d-none').text(
                        'Expiry time must be between 1 and 120 minutes.');
                    hasError = true;
                }

                const host = $('#txtSmtpHost').val() ? $('#txtSmtpHost').val().trim() : '';
                const port = $('#txtSmtpPort').val() ? parseInt($('#txtSmtpPort').val(), 10) : null;
                const fromAddress = $('#txtSmtpFromEmail').val() ? $('#txtSmtpFromEmail').val().trim() : '';

                if (!host) {
                    $('#lblSmtpHostError').removeClass('d-none').text('SMTP Host is required.');
                    hasError = true;
                }
                if (!port || port < 1 || port > 65535) {
                    $('#lblSmtpPortError').removeClass('d-none').text(
                        'Valid SMTP Port (1-65535) is required.');
                    hasError = true;
                }
                if (!fromAddress) {
                    $('#lblSmtpFromEmailError').removeClass('d-none').text('From Email is required.');
                    hasError = true;
                }

                if (hasError) {
                    return;
                }

                const payload = {
                    host: host,
                    port: port,
                    username: $('#txtSmtpUsername').val() ? $('#txtSmtpUsername').val().trim() : '',
                    password: $('#txtSmtpPassword').val() ? $('#txtSmtpPassword').val() : '',
                    encryption: $('#ddlSmtpEncryption').val() ? $('#ddlSmtpEncryption').val().trim() :
                        'none',
                    from_address: fromAddress,
                    from_name: $('#txtSmtpFromName').val() ? $('#txtSmtpFromName').val().trim() : '',
                    otpattempt: attempts,
                    otpresetTime: reset,
                    otpexpiryTime: expiry
                };

                $.ajax({
                    url: '{{ route('updateSmtpSettings') }}',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(payload),
                    success: function(response) {
                        if (response.result === true) {
                            $('#txtEmailOtpAttempts').data('savedValue', attempts);
                            $('#txtEmailOtpReset').data('savedValue', reset);
                            $('#txtEmailOtpExpiry').data('savedValue', expiry);
                            notificationToastCustom('success', response.message, 'Success');
                            loadEmailConfig();
                            loadAuthSettings();

                            if (window.bootstrap && bootstrap.Offcanvas) {
                                const sidebar = document.getElementById('btnEmailSidebar');
                                const offcanvasInstance = bootstrap.Offcanvas.getInstance(
                                    sidebar);
                                if (offcanvasInstance) {
                                    offcanvasInstance.hide();
                                }
                            }
                        } else {
                            notificationToastCustom('error', response.message ||
                                'Unable to save SMTP settings.', 'Error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('SMTP settings save error:', error);

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            if (errors.otpattempt) $('#lblEmailOtpAttemptsError').removeClass(
                                'd-none').text(errors.otpattempt[0]);
                            if (errors.otpresetTime) $('#lblEmailOtpResetError').removeClass(
                                'd-none').text(errors.otpresetTime[0]);
                            if (errors.otpexpiryTime) $('#lblEmailOtpExpiryError').removeClass(
                                'd-none').text(errors.otpexpiryTime[0]);
                            if (errors.host) $('#lblSmtpHostError').removeClass('d-none').text(
                                errors.host[0]);
                            if (errors.port) $('#lblSmtpPortError').removeClass('d-none').text(
                                errors.port[0]);
                            if (errors.username) $('#lblSmtpUsernameError').removeClass(
                                'd-none').text(errors.username[0]);
                            if (errors.password) $('#lblSmtpPasswordError').removeClass(
                                'd-none').text(errors.password[0]);
                            if (errors.encryption) $('#lblSmtpEncryptionError').removeClass(
                                'd-none').text(errors.encryption[0]);
                            if (errors.from_address) $('#lblSmtpFromEmailError').removeClass(
                                'd-none').text(errors.from_address[0]);
                            if (errors.from_name) $('#lblSmtpFromNameError').removeClass(
                                'd-none').text(errors.from_name[0]);
                        }

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            notificationToastCustom('error', xhr.responseJSON.message, 'Error');
                        } else if (xhr.status === 419) {
                            notificationToastCustom('error',
                                'Session expired. Please refresh the page.', 'CSRF Error');
                        } else {
                            notificationToastCustom('error', 'Failed to save SMTP settings');
                        }
                    }
                });
            });

            $('#btnTestEmail').on('click', function() {
                $('#lblTestRecipientEmailError').addClass('d-none').text('');

                const recipientEmail = $('#txtTestRecipientEmail').val() ? $('#txtTestRecipientEmail').val()
                    .trim() : '';

                if (!recipientEmail) {
                    $('#lblTestRecipientEmailError').removeClass('d-none').text(
                        'Please enter a recipient email address.');
                    notificationToastCustom('error', 'Recipient email is required.', 'Validation Error');
                    $('#txtTestRecipientEmail').focus();
                    return;
                }

                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(recipientEmail)) {
                    $('#lblTestRecipientEmailError').removeClass('d-none').text(
                        'Please enter a valid email address.');
                    notificationToastCustom('error', 'Please enter a valid recipient email address.',
                        'Validation Error');
                    $('#txtTestRecipientEmail').focus();
                    return;
                }

                if (!savedEmailConfigSnapshot || !savedEmailConfigSnapshot.is_configured) {
                    notificationToastCustom('error',
                        'SMTP configuration is incomplete or not saved yet. Please configure and save SMTP settings first.',
                        'Configuration Required');
                    return;
                }

                if (hasUnsavedEmailChanges()) {
                    notificationToastCustom('warning',
                        'You have unsaved SMTP configuration changes. Please save the settings before sending a test email.',
                        'Unsaved Changes');
                    return;
                }

                const $btn = $('#btnTestEmail');
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sending...'
                );

                const payload = {
                    recipient_email: recipientEmail,
                    host: $('#txtSmtpHost').val() ? $('#txtSmtpHost').val().trim() : '',
                    port: $('#txtSmtpPort').val() ? parseInt($('#txtSmtpPort').val(), 10) : null,
                    username: $('#txtSmtpUsername').val() ? $('#txtSmtpUsername').val().trim() : '',
                    encryption: $('#ddlSmtpEncryption').val() ? $('#ddlSmtpEncryption').val().trim() :
                        'none',
                    from_address: $('#txtSmtpFromEmail').val() ? $('#txtSmtpFromEmail').val().trim() :
                        '',
                    from_name: $('#txtSmtpFromName').val() ? $('#txtSmtpFromName').val().trim() : '',
                    password: $('#txtSmtpPassword').val() ? $('#txtSmtpPassword').val() : ''
                };

                $.ajax({
                    url: '{{ route('sendTestEmail') }}',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(payload),
                    success: function(response) {
                        if (response.result === true) {
                            notificationToastCustom('success', response.message ||
                                'Test email sent successfully to the specified email address.',
                                'Success');
                        } else {
                            notificationToastCustom('error', response.message ||
                                'Failed to send test email.', 'Error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('SMTP test email error:', error);

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            if (errors.recipient_email) {
                                $('#lblTestRecipientEmailError').removeClass('d-none').text(
                                    errors.recipient_email[0]);
                            }
                        }

                        const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message :
                            'Failed to send test email due to mail server error.';
                        notificationToastCustom('error', errorMsg, 'Error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });

            /* ===============================
               WhatsApp Sidebar
            ================================ */
            $('#whatsappConfigBtn').on('click', function() {
                clearWhatsAppErrors();
                loadWhatsAppConfig();
            });

            $('#btnWhatsAppSidebar').on('show.bs.offcanvas', function() {
                clearWhatsAppErrors();
                loadWhatsAppConfig();
            });

            $('#btnCancelWhatsApp').on('click', function() {
                clearWhatsAppErrors();
                $('#txtWhatsAppOtpAttempts').val($('#txtWhatsAppOtpAttempts').data('savedValue') || '');
                $('#txtWhatsAppOtpReset').val($('#txtWhatsAppOtpReset').data('savedValue') || '');
                $('#txtWhatsAppOtpExpiry').val($('#txtWhatsAppOtpExpiry').data('savedValue') || '');
                loadWhatsAppConfig();
                notificationToastCustom('info', 'WhatsApp settings reset.', 'Information');
            });

            $('#btnSaveWhatsApp').on('click', function() {
                clearWhatsAppErrors();
                let hasError = false;

                const attempts = $('#txtWhatsAppOtpAttempts').val() ? parseInt($('#txtWhatsAppOtpAttempts')
                    .val(), 10) : null;
                const reset = $('#txtWhatsAppOtpReset').val() ? parseInt($('#txtWhatsAppOtpReset').val(),
                    10) : null;
                const expiry = $('#txtWhatsAppOtpExpiry').val() ? parseInt($('#txtWhatsAppOtpExpiry').val(),
                    10) : null;

                if (attempts !== null && (attempts < 1 || attempts > 10)) {
                    $('#lblWhatsAppOtpAttemptsError').removeClass('d-none').text(
                        'Attempts must be between 1 and 10.');
                    hasError = true;
                }
                if (reset !== null && (reset < 1 || reset > 60)) {
                    $('#lblWhatsAppOtpResetError').removeClass('d-none').text(
                        'Reset time must be between 1 and 60 minutes.');
                    hasError = true;
                }
                if (expiry !== null && (expiry < 1 || expiry > 120)) {
                    $('#lblWhatsAppOtpExpiryError').removeClass('d-none').text(
                        'Expiry time must be between 1 and 120 minutes.');
                    hasError = true;
                }

                const url = $('#txtWhatsAppUrl').val() ? $('#txtWhatsAppUrl').val().trim() : '';
                const senderId = $('#txtWhatsAppSenderId').val() ? $('#txtWhatsAppSenderId').val().trim() :
                    '';
                const template = $('#txtWhatsAppTemplate').val() ? $('#txtWhatsAppTemplate').val().trim() :
                    '';
                const timeout = $('#txtWhatsAppTimeout').val() ? parseInt($('#txtWhatsAppTimeout').val(),
                    10) : null;

                if (!url) {
                    $('#lblWhatsAppUrlError').removeClass('d-none').text('Gateway URL is required.');
                    hasError = true;
                }
                if (!senderId) {
                    $('#lblWhatsAppSenderIdError').removeClass('d-none').text('Sender ID is required.');
                    hasError = true;
                }
                if (!template) {
                    $('#lblWhatsAppTemplateError').removeClass('d-none').text('Template is required.');
                    hasError = true;
                }
                if (!timeout || timeout < 1 || timeout > 120) {
                    $('#lblWhatsAppTimeoutError').removeClass('d-none').text(
                        'Timeout must be between 1 and 120 seconds.');
                    hasError = true;
                }

                if (hasError) {
                    return;
                }

                const payload = {
                    url: url,
                    token: $('#txtWhatsAppToken').val() ? $('#txtWhatsAppToken').val().trim() : '',
                    username: $('#txtWhatsAppUsername').val() ? $('#txtWhatsAppUsername').val().trim() :
                        '',
                    password: $('#txtWhatsAppPassword').val() ? $('#txtWhatsAppPassword').val() : '',
                    sender_id: senderId,
                    template: template,
                    timeout: timeout,
                    verify_ssl: $('#chkWhatsAppVerifySsl').is(':checked'),
                    otpattempt: attempts,
                    otpresetTime: reset,
                    otpexpiryTime: expiry
                };

                $.ajax({
                    url: '{{ route('updateWhatsAppGatewaySettings') }}',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(payload),
                    success: function(response) {
                        if (response.result === true) {
                            $('#txtWhatsAppOtpAttempts').data('savedValue', attempts);
                            $('#txtWhatsAppOtpReset').data('savedValue', reset);
                            $('#txtWhatsAppOtpExpiry').data('savedValue', expiry);
                            notificationToastCustom('success', response.message, 'Success');
                            loadWhatsAppConfig();
                            loadAuthSettings();

                            if (window.bootstrap && bootstrap.Offcanvas) {
                                const sidebar = document.getElementById('btnWhatsAppSidebar');
                                const offcanvasInstance = bootstrap.Offcanvas.getInstance(
                                    sidebar);
                                if (offcanvasInstance) {
                                    offcanvasInstance.hide();
                                }
                            }
                        } else {
                            notificationToastCustom('error', response.message ||
                                'Unable to save WhatsApp settings.', 'Error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('WhatsApp settings save error:', error);

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            if (errors.otpattempt) $('#lblWhatsAppOtpAttemptsError')
                                .removeClass('d-none').text(errors.otpattempt[0]);
                            if (errors.otpresetTime) $('#lblWhatsAppOtpResetError').removeClass(
                                'd-none').text(errors.otpresetTime[0]);
                            if (errors.otpexpiryTime) $('#lblWhatsAppOtpExpiryError')
                                .removeClass('d-none').text(errors.otpexpiryTime[0]);
                            if (errors.url) $('#lblWhatsAppUrlError').removeClass('d-none')
                                .text(errors.url[0]);
                            if (errors.token) $('#lblWhatsAppTokenError').removeClass('d-none')
                                .text(errors.token[0]);
                            if (errors.username) $('#lblWhatsAppUsernameError').removeClass(
                                'd-none').text(errors.username[0]);
                            if (errors.password) $('#lblWhatsAppPasswordError').removeClass(
                                'd-none').text(errors.password[0]);
                            if (errors.sender_id) $('#lblWhatsAppSenderIdError').removeClass(
                                'd-none').text(errors.sender_id[0]);
                            if (errors.template) $('#lblWhatsAppTemplateError').removeClass(
                                'd-none').text(errors.template[0]);
                            if (errors.timeout) $('#lblWhatsAppTimeoutError').removeClass(
                                'd-none').text(errors.timeout[0]);
                        }

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            notificationToastCustom('error', xhr.responseJSON.message, 'Error');
                        } else if (xhr.status === 419) {
                            notificationToastCustom('error',
                                'Session expired. Please refresh the page.', 'CSRF Error');
                        } else {
                            notificationToastCustom('error',
                                'Failed to save WhatsApp gateway settings');
                        }
                    }
                });
            });

            /* ===============================
               Mobile OTP Sidebar
            ================================ */
            $('#mobileOtpConfigBtn').on('click', function() {
                clearMobileOtpErrors();
                loadMobileOtpConfig();
            });

            $('#btnMobileOtpSidebar').on('show.bs.offcanvas', function() {
                clearMobileOtpErrors();
                loadMobileOtpConfig();
            });

            $('#btnCancelMobileOtp').on('click', function() {
                clearMobileOtpErrors();
                $('#txtMobileOtpAttempts').val($('#txtMobileOtpAttempts').data('savedValue') || '');
                $('#txtMobileOtpReset').val($('#txtMobileOtpReset').data('savedValue') || '');
                $('#txtMobileOtpExpiry').val($('#txtMobileOtpExpiry').data('savedValue') || '');
                loadMobileOtpConfig();
                notificationToastCustom('info', 'Mobile OTP settings reset.', 'Information');
            });

            $('#btnSaveMobileOtp').on('click', function() {
                clearMobileOtpErrors();
                let hasError = false;

                const attempts = $('#txtMobileOtpAttempts').val() ? parseInt($('#txtMobileOtpAttempts')
                    .val(), 10) : null;
                const reset = $('#txtMobileOtpReset').val() ? parseInt($('#txtMobileOtpReset').val(), 10) :
                    null;
                const expiry = $('#txtMobileOtpExpiry').val() ? parseInt($('#txtMobileOtpExpiry').val(),
                    10) : null;

                if (attempts !== null && (attempts < 1 || attempts > 10)) {
                    $('#lblMobileOtpAttemptsError').removeClass('d-none').text(
                        'Attempts must be between 1 and 10.');
                    hasError = true;
                }
                if (reset !== null && (reset < 1 || reset > 60)) {
                    $('#lblMobileOtpResetError').removeClass('d-none').text(
                        'Reset time must be between 1 and 60 minutes.');
                    hasError = true;
                }
                if (expiry !== null && (expiry < 1 || expiry > 120)) {
                    $('#lblMobileOtpExpiryError').removeClass('d-none').text(
                        'Expiry time must be between 1 and 120 minutes.');
                    hasError = true;
                }

                const url = $('#txtMobileOtpUrl').val() ? $('#txtMobileOtpUrl').val().trim() : '';
                const timeout = $('#txtMobileOtpTimeout').val() ? parseInt($('#txtMobileOtpTimeout').val(),
                    10) : null;

                if (!url) {
                    $('#lblMobileOtpUrlError').removeClass('d-none').text('Gateway URL is required.');
                    hasError = true;
                }
                if (!timeout || timeout < 1 || timeout > 120) {
                    $('#lblMobileOtpTimeoutError').removeClass('d-none').text(
                        'Timeout must be between 1 and 120 seconds.');
                    hasError = true;
                }

                if (hasError) {
                    return;
                }

                const payload = {
                    url: url,
                    token: $('#txtMobileOtpToken').val() ? $('#txtMobileOtpToken').val().trim() : '',
                    username: $('#txtMobileOtpUsername').val() ? $('#txtMobileOtpUsername').val()
                        .trim() : '',
                    password: $('#txtMobileOtpPassword').val() ? $('#txtMobileOtpPassword').val() : '',
                    sender_id: $('#txtMobileOtpSenderId').val() ? $('#txtMobileOtpSenderId').val()
                        .trim() : '',
                    template: $('#txtMobileOtpTemplate').val() ? $('#txtMobileOtpTemplate').val()
                        .trim() : '',
                    timeout: timeout,
                    verify_ssl: $('#chkMobileOtpVerifySsl').is(':checked'),
                    otpattempt: attempts,
                    otpresetTime: reset,
                    otpexpiryTime: expiry
                };

                $.ajax({
                    url: '{{ route('updateMobileOtpGatewaySettings') }}',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(payload),
                    success: function(response) {
                        if (response.result === true) {
                            $('#txtMobileOtpAttempts').data('savedValue', attempts);
                            $('#txtMobileOtpReset').data('savedValue', reset);
                            $('#txtMobileOtpExpiry').data('savedValue', expiry);
                            notificationToastCustom('success', response.message, 'Success');
                            loadMobileOtpConfig();
                            loadAuthSettings();

                            if (window.bootstrap && bootstrap.Offcanvas) {
                                const sidebar = document.getElementById('btnMobileOtpSidebar');
                                const offcanvasInstance = bootstrap.Offcanvas.getInstance(
                                    sidebar);
                                if (offcanvasInstance) {
                                    offcanvasInstance.hide();
                                }
                            }
                        } else {
                            notificationToastCustom('error', response.message ||
                                'Unable to save Mobile OTP settings.', 'Error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Mobile OTP settings save error:', error);

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            if (errors.otpattempt) $('#lblMobileOtpAttemptsError').removeClass(
                                'd-none').text(errors.otpattempt[0]);
                            if (errors.otpresetTime) $('#lblMobileOtpResetError').removeClass(
                                'd-none').text(errors.otpresetTime[0]);
                            if (errors.otpexpiryTime) $('#lblMobileOtpExpiryError').removeClass(
                                'd-none').text(errors.otpexpiryTime[0]);
                            if (errors.url) $('#lblMobileOtpUrlError').removeClass('d-none')
                                .text(errors.url[0]);
                            if (errors.token) $('#lblMobileOtpTokenError').removeClass('d-none')
                                .text(errors.token[0]);
                            if (errors.username) $('#lblMobileOtpUsernameError').removeClass(
                                'd-none').text(errors.username[0]);
                            if (errors.password) $('#lblMobileOtpPasswordError').removeClass(
                                'd-none').text(errors.password[0]);
                            if (errors.sender_id) $('#lblMobileOtpSenderIdError').removeClass(
                                'd-none').text(errors.sender_id[0]);
                            if (errors.template) $('#lblMobileOtpTemplateError').removeClass(
                                'd-none').text(errors.template[0]);
                            if (errors.timeout) $('#lblMobileOtpTimeoutError').removeClass(
                                'd-none').text(errors.timeout[0]);
                        }

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            notificationToastCustom('error', xhr.responseJSON.message, 'Error');
                        } else if (xhr.status === 419) {
                            notificationToastCustom('error',
                                'Session expired. Please refresh the page.', 'CSRF Error');
                        } else {
                            notificationToastCustom('error',
                                'Failed to save Mobile OTP SMS gateway settings');
                        }
                    }
                });
            });

            // Update password rules
            $('#updatePasswordRules').on('click', function(e) {
                e.preventDefault();

                let rules = [{
                        RuleId: $('#minLength').data('ruleid') || 0,
                        RuleCode: 'MIN_LENGTH',
                        IsEnabled: $('#minLength').is(':checked'),
                        RuleValue: 8
                    },
                    {
                        RuleId: $('#uppercase').data('ruleid') || 0,
                        RuleCode: 'UPPERCASE',
                        IsEnabled: $('#uppercase').is(':checked'),
                        RuleValue: 1
                    },
                    {
                        RuleId: $('#lowercase').data('ruleid') || 0,
                        RuleCode: 'LOWERCASE',
                        IsEnabled: $('#lowercase').is(':checked'),
                        RuleValue: 1
                    },
                    {
                        RuleId: $('#number').data('ruleid') || 0,
                        RuleCode: 'NUMBER',
                        IsEnabled: $('#number').is(':checked'),
                        RuleValue: 1
                    },
                    {
                        RuleId: $('#specialChar').data('ruleid') || 0,
                        RuleCode: 'SPECIAL_CHAR',
                        IsEnabled: $('#specialChar').is(':checked'),
                        RuleValue: 1
                    }
                ];

                console.log('Sending password rules:', rules);

                $.ajax({
                    url: '{{ route('updatePasswordRules') }}',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(rules),
                    success: function(response) {
                        if (response.result == true) {
                            notificationToastCustom('success', response.message, "Success");
                            // Reload rules to get updated IDs if any
                            loadPasswordRules();
                        } else {
                            notificationToastCustom('error', response.message, "Error");
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Update error:', error);
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            notificationToastCustom('error', xhr.responseJSON.message, "Error");
                        } else if (xhr.status === 419) {
                            notificationToastCustom('error',
                                'Session expired. Please refresh the page.', 'CSRF Error');
                        } else {
                            notificationToastCustom('error', "Failed to update password rules");
                        }
                    }
                });
            });

            /* ===============================
               MAC Functionality Sidebar
            ================================ */
            let cachedMacUsers = [];
            let currentMacFilter = 'all';

            function escapeHtml(str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function isValidMacFormat(mac) {
                if (!mac || typeof mac !== 'string') return false;
                const trimmed = mac.trim();
                if (/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/.test(trimmed)) return true;
                if (/^([0-9A-Fa-f]{4}\.){2}[0-9A-Fa-f]{4}$/.test(trimmed)) return true;
                if (/^[0-9A-Fa-f]{12}$/.test(trimmed)) return true;
                return false;
            }

            function loadMacUsers(search = '') {
                $('#macUsersLoading').removeClass('d-none');
                $('#macUsersEmpty').addClass('d-none');
                $('#macUsersContainer').empty();

                $.ajax({
                    url: '{{ route('getUserMacSettings') }}',
                    type: 'GET',
                    data: {
                        search: search
                    },
                    success: function(response) {
                        $('#macUsersLoading').addClass('d-none');
                        if (response && response.result) {
                            cachedMacUsers = response.users || [];
                            $('#macTotalUsers').text(response.totalUsers || 0);
                            $('#macConfiguredUsers').text(response.configuredUsers || 0);
                            $('#macUnconfiguredUsers').text(response.unconfiguredUsers || 0);
                            renderMacUsers();
                        } else {
                            notificationToastCustom('error', response.message ||
                                'Failed to load user list', 'Error');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#macUsersLoading').addClass('d-none');
                        console.error('Error loading MAC user list:', error);
                        if (xhr.status === 403) {
                            notificationToastCustom('error', 'Unauthorized to view MAC settings',
                                'Permission Denied');
                        } else {
                            notificationToastCustom('error', 'Failed to load user list', 'Error');
                        }
                    }
                });
            }

            function renderMacUsers() {
                const $container = $('#macUsersContainer');
                $container.empty();

                const query = ($('#txtMacUserSearch').val() || '').trim().toLowerCase();

                let filtered = cachedMacUsers.filter(function(user) {
                    if (currentMacFilter === 'configured' && !user.has_mac) {
                        return false;
                    }
                    if (currentMacFilter === 'unconfigured' && user.has_mac) {
                        return false;
                    }

                    if (query !== '') {
                        const name = (user.name || '').toLowerCase();
                        const email = (user.email || '').toLowerCase();
                        const mobile = (user.mobile_number || '').toLowerCase();
                        const mac = (user.device_mac || '').toLowerCase();
                        if (!name.includes(query) && !email.includes(query) && !mobile.includes(query) && !
                            mac.includes(query)) {
                            return false;
                        }
                    }

                    return true;
                });

                if (filtered.length === 0) {
                    $('#macUsersEmpty').removeClass('d-none');
                    return;
                }

                $('#macUsersEmpty').addClass('d-none');

                filtered.forEach(function(user) {
                    let statusBadge = '';
                    if (user.has_mac) {
                        if (user.is_authorized) {
                            statusBadge =
                                '<span class="badge bg-success-subtle text-success border border-success-subtle user-status-badge"><i class="fas fa-check-circle me-1"></i>Authorized</span>';
                        } else {
                            statusBadge =
                                '<span class="badge bg-warning-subtle text-warning border border-warning-subtle user-status-badge"><i class="fas fa-ban me-1"></i>Unauthorized</span>';
                        }
                    } else {
                        statusBadge =
                            '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle user-status-badge"><i class="fas fa-minus-circle me-1"></i>Not Configured</span>';
                    }

                    const mobileHtml = user.mobile_number ?
                        `<div class="small text-muted user-mobile"><i class="fas fa-phone me-1"></i>${escapeHtml(user.mobile_number)}</div>` :
                        `<div class="small text-muted fst-italic user-mobile"><i class="fas fa-phone me-1"></i>No mobile number</div>`;

                    const macDisplayHtml = user.has_mac ?
                        `<code class="fw-bold text-dark fs-6 current-mac">${escapeHtml(user.device_mac)}</code>${user.device_name ? `<span class="badge bg-light text-muted border ms-2 small device-name-badge">${escapeHtml(user.device_name)}</span>` : ''}` :
                        `<span class="text-muted small fst-italic current-mac">None</span>`;

                    const btnActionText = user.has_mac ? 'Update' : 'Add MAC Address';
                    const btnActionIcon = user.has_mac ? 'fa-edit' : 'fa-plus';
                    const btnActionClass = user.has_mac ? 'btn-outline-primary' : 'btn-primary';

                    const deleteBtnHtml = user.has_mac ? `
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-mac ms-1" 
                                data-user-id="${user.id}" 
                                data-device-id="${user.device_id || ''}" 
                                data-user-name="${escapeHtml(user.name)}"
                                data-mac="${escapeHtml(user.device_mac || '')}">
                            <i class="fas fa-trash-alt me-1"></i>Delete
                        </button>
                    ` : '';

                    const cardHtml = `
                    <div class="card border user-mac-card mb-2" data-user-id="${user.id}">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark user-name">
                                        ${escapeHtml(user.name)}
                                    </h6>

                                    <div class="d-flex align-items-center flex-wrap gap-3 small text-muted">
                                        <span class="user-email">
                                            <i class="fas fa-envelope me-1"></i>${escapeHtml(user.email)}
                                        </span>
                                        |
                                        <span>
                                            ${mobileHtml}
                                        </span>
                                    </div>
                                </div>

                                <div class="status-container">
                                    ${statusBadge}
                                </div>
                            </div>

                            <div class="mac-display-row d-flex justify-content-between align-items-center bg-light p-2 rounded border">
                                <div>
                                    <span class="small text-muted me-2">MAC:</span>
                                    ${macDisplayHtml}
                                </div>
                                <div class="d-flex align-items-center">
                                    <button type="button" class="btn btn-sm ${btnActionClass} btn-toggle-edit" data-user-id="${user.id}">
                                        <i class="fas ${btnActionIcon} me-1"></i>${btnActionText}
                                    </button>
                                    ${deleteBtnHtml}
                                </div>
                            </div>

                            <div class="mac-edit-panel d-none mt-3 pt-3 border-top">
                                <div class="row g-2">
                                    <div class="col-md-7">
                                        <label class="form-label small fw-bold">MAC Address <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm input-user-mac" 
                                            placeholder="e.g. 00:1A:2B:3C:4D:5E" 
                                            value="${escapeHtml(user.device_mac || '')}"
                                            maxlength="20">
                                        <div class="text-danger small field-error d-none input-user-mac-error"></div>
                                        <div class="small-note text-muted" style="font-size: 0.75rem;">Format: 00:1A:2B:3C:4D:5E</div>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold">Device Name</label>
                                        <input type="text" class="form-control form-control-sm input-device-name" 
                                            placeholder="e.g. Workstation" 
                                            value="${escapeHtml(user.device_name || 'Authorized Device')}">
                                    </div>
                                   <div class="col-12 mt-2">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                                        <!-- Left Side: Switch -->
                                        <div class="form-check form-switch form-switch-sm mb-0">
                                            <input
                                                class="form-check-input input-device-authorized"
                                                type="checkbox"
                                                id="authCheck_${user.id}"
                                                ${user.is_authorized ? 'checked' : ''}
                                            >
                                            <label
                                                class="form-check-label small"
                                                for="authCheck_${user.id}">
                                                Device Authorized for Login
                                            </label>
                                        </div>

                                        <!-- Right Side: Buttons -->
                                        <div class="d-flex align-items-center gap-2 ms-auto">
                                            <button
                                                type="button"
                                                class="btn btn-secondary btn-sm px-3 btn-cancel-edit"
                                                data-user-id="${user.id}">
                                                <i class="fas fa-times me-1"></i>Cancel
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm px-4 fw-bold btn-save-user-mac"
                                                data-user-id="${user.id}"
                                                data-device-id="${user.device_id || ''}">
                                                <i class="fas fa-save me-1"></i>Save
                                            </button>
                                        </div>

                                    </div>
                                  
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                    $container.append(cardHtml);
                });
            }

            $('#macConfigBtn').on('click', function() {
                loadMacUsers();
            });

            $('#btnMacSidebar').on('show.bs.offcanvas', function() {
                loadMacUsers();
            });

            $('#txtMacUserSearch').on('input', function() {
                renderMacUsers();
            });

            $('#btnRefreshMacUsers').on('click', function() {
                loadMacUsers();
            });

            $('#macFilterButtonGroup button').on('click', function() {
                $('#macFilterButtonGroup button').removeClass('active');
                $(this).addClass('active');
                currentMacFilter = $(this).data('filter');
                renderMacUsers();
            });

            $(document).on('click', '.btn-toggle-edit', function() {
                const $card = $(this).closest('.user-mac-card');
                const $panel = $card.find('.mac-edit-panel');
                $panel.toggleClass('d-none');
                if (!$panel.hasClass('d-none')) {
                    $panel.find('.input-user-mac').focus();
                    setTimeout(function() {
                        $panel[0].scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                    }, 100);
                }
            });

            $(document).on('click', '.btn-cancel-edit', function() {
                const $card = $(this).closest('.user-mac-card');
                const userId = $(this).data('user-id');
                const user = cachedMacUsers.find(u => u.id == userId);
                const $panel = $card.find('.mac-edit-panel');
                $panel.addClass('d-none');
                $panel.find('.input-user-mac-error').addClass('d-none').text('');
                if (user) {
                    $panel.find('.input-user-mac').val(user.device_mac || '');
                    $panel.find('.input-device-name').val(user.device_name || 'Authorized Device');
                    $panel.find('.input-device-authorized').prop('checked', !!user.is_authorized);
                }
            });

            $(document).on('click', '.btn-save-user-mac', function() {
                const $btn = $(this);
                const $card = $btn.closest('.user-mac-card');
                const userId = $btn.data('user-id');
                const deviceId = $btn.data('device-id');
                const $macInput = $card.find('.input-user-mac');
                const $errorDiv = $card.find('.input-user-mac-error');
                const $nameInput = $card.find('.input-device-name');
                const isAuthorized = $card.find('.input-device-authorized').is(':checked');

                $errorDiv.addClass('d-none').text('');

                const rawMac = $macInput.val().trim();
                if (!rawMac) {
                    $errorDiv.removeClass('d-none').text('MAC address is required.');
                    notificationToastCustom('warning', 'MAC address is required.', 'Validation');
                    return;
                }

                if (!isValidMacFormat(rawMac)) {
                    $errorDiv.removeClass('d-none').text('Invalid MAC format. Example: 00:1A:2B:3C:4D:5E');
                    notificationToastCustom('warning', 'Invalid MAC address format.', 'Validation');
                    return;
                }

                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

                $.ajax({
                    url: '{{ route('updateUserMacSetting') }}',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        user_id: userId,
                        device_id: deviceId || null,
                        device_mac: rawMac,
                        device_name: $nameInput.val().trim(),
                        is_authorized: isAuthorized
                    }),
                    success: function(response) {
                        $btn.prop('disabled', false).html(
                            '<i class="fas fa-save me-1"></i>Save');
                        if (response && response.result) {
                            notificationToastCustom('success', response.message ||
                                'MAC address saved.', 'Success');
                            loadMacUsers($('#txtMacUserSearch').val().trim());
                        } else {
                            notificationToastCustom('error', response.message ||
                                'Failed to save MAC address.', 'Error');
                        }
                    },
                    error: function(xhr, status, error) {
                        $btn.prop('disabled', false).html(
                            '<i class="fas fa-save me-1"></i>Save');
                        console.error('Error saving user MAC:', error);
                        if (xhr.status === 422 && xhr.responseJSON) {
                            let msg = xhr.responseJSON.message || 'Validation error';
                            if (xhr.responseJSON.errors && xhr.responseJSON.errors.device_mac) {
                                msg = xhr.responseJSON.errors.device_mac[0];
                            }
                            $errorDiv.removeClass('d-none').text(msg);
                            notificationToastCustom('error', msg, 'Validation Error');
                        } else if (xhr.status === 403) {
                            notificationToastCustom('error',
                                'Unauthorized to modify MAC configurations.',
                                'Permission Denied');
                        } else if (xhr.status === 419) {
                            notificationToastCustom('error',
                                'Session expired. Please refresh the page.', 'CSRF Error');
                        } else {
                            notificationToastCustom('error',
                                'Failed to save MAC configuration due to a server error.',
                                'Error');
                        }
                    }
                });
            });

            // Delete User MAC Address Handler
            $(document).on('click', '.btn-delete-mac', function() {
                const $btn = $(this);
                const userId = $btn.data('user-id');
                const deviceId = $btn.data('device-id');
                const userName = $btn.data('user-name') || 'this user';
                const mac = $btn.data('mac');

                Swal.fire({
                    title: 'Delete MAC Address?',
                    text: 'Are you sure you want to delete this MAC address' + (mac ? ' (' + mac + ')' : '') + ' for ' + userName + '?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f46a6a',
                    cancelButtonColor: '#74788d',
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Deleting...');

                        $.ajax({
                            url: '{{ route('deleteUserMacSetting') }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                user_id: userId,
                                device_id: deviceId || null
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response && response.result) {
                                    notificationToastCustom('success', response.message || 'MAC address deleted successfully.', 'Success');
                                    loadMacUsers($('#txtMacUserSearch').val().trim());
                                } else {
                                    $btn.prop('disabled', false).html('<i class="fas fa-trash-alt me-1"></i>Delete');
                                    notificationToastCustom('error', response.message || 'Failed to delete MAC address.', 'Error');
                                }
                            },
                            error: function(xhr, status, error) {
                                $btn.prop('disabled', false).html('<i class="fas fa-trash-alt me-1"></i>Delete');
                                let msg = 'Failed to delete MAC address.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    msg = xhr.responseJSON.message;
                                }
                                if (xhr.status === 403) {
                                    notificationToastCustom('error', 'Unauthorized to delete MAC configurations.', 'Permission Denied');
                                } else {
                                    notificationToastCustom('error', msg, 'Error');
                                }
                            }
                        });
                    }
                });
            });
        });

        function loadPasswordRules() {
            $.ajax({
                url: '{{ route('getPasswordRules') }}',
                type: 'GET',
                success: function(rules) {
                    console.log('Loaded password rules:', rules);
                    let list = Array.isArray(rules) ? rules : (rules && rules.data ? rules.data : []);
                    if (list && list.length > 0) {
                        list.forEach(r => {
                            let checkbox = null;
                            let code = r.ruleCode || r.RuleCode;

                            switch (code) {
                                case 'MIN_LENGTH':
                                    checkbox = $('#minLength');
                                    break;
                                case 'UPPERCASE':
                                    checkbox = $('#uppercase');
                                    break;
                                case 'LOWERCASE':
                                    checkbox = $('#lowercase');
                                    break;
                                case 'NUMBER':
                                    checkbox = $('#number');
                                    break;
                                case 'SPECIAL_CHAR':
                                    checkbox = $('#specialChar');
                                    break;
                            }

                            if (checkbox) {
                                let isChecked = r.isEnabled !== undefined ? r.isEnabled : r.IsEnabled;
                                let ruleId = r.ruleId !== undefined ? r.ruleId : r.RuleId;
                                checkbox.prop('checked', !!isChecked);
                                checkbox.attr('data-ruleid', ruleId);
                                checkbox.data('ruleid', ruleId);
                            }
                        });
                    } else {
                        console.warn('No password rules returned from server');
                        // Initialize with default IDs as 0
                        $('#minLength, #uppercase, #lowercase, #number, #specialChar').each(function() {
                            $(this).data('ruleid', 0);
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading password rules:', error);
                    notificationToastCustom('error', 'Failed to load password rules');
                }
            });
        }
    </script>
@endsection
