
/* =======================
      GLOBAL VARIABLES
   ======================= */
const MAX_OTP_ATTEMPTS = 0;

let emailOtpAttempts = 0;
let mobileOtpAttempts = 0;
let twoFactorOtpAttempts = 0;

let authSteps = [];
let currentStepIndex = 0;
let isCaptchaVerified = false;
let qrGenerated = false; // QR generated first time

let otpSettings = {};
let otpAttempts = {};
// =======================
// GLOBAL VARIABLES
// =======================
let resendTimers = {};   // ✅ FIX
let resendIntervals = {};     // interval references ✅ FIX

// Initially hide button
//$('#btnShowQRAgain').hide();

const STEP_ORDER = ["captcha", "email_verify", "mobile_verify", "two_factor"];


$(document).ready(function () {

    showScreen("loginContainer");
    // Prevent flash
    $('.auth-screen').removeClass('active');

    // Init immediately
    initAuthSettings(window.AUTH_SETTINGS);

    restoreResendTimers();

    /* =======================
       TOASTR CONFIG
    ======================= */
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000,
        extendedTimeOut: 1000,
        preventDuplicates: true,
        newestOnTop: true
    };

    $('#resendLink').on('click', function () {

        if ($(this).hasClass('disabled')) return;

        const type = authSteps[currentStepIndex];

        onOtpSent(type);   // 🔥 restart resend timer
    });

    $('#resendLinkMobile').on('click', function () {

        if ($(this).hasClass('disabled')) return;

        const type = authSteps[currentStepIndex];

        onOtpSent(type);   // 🔥 restart resend timer
    });


    //$("#txtCaptcha", "#txtEmail", "#txtPass").on("keyup", function () {
    $("#txtCaptcha").on("keyup", function () {

        var captcha = $("#txtCaptcha").val().trim();
        if (captcha !== "") {
            $("#captchaErrorMsg").addClass("d-none").removeClass("d-flex").text("");
        } else {
            $("#captchaErrorMsg").text("Please Enter Captcha").removeClass("d-none").addClass("d-flex");

        }

    });

    $("#txtEmail").on("keyup", function () {
        var email = $("#txtEmail").val().trim();
        if (email !== "") {

            $("#lblemailError").addClass("d-none").text("");
        }
        else {
            $("#lblemailError").text("Please enter Username.").removeClass("d-none");

        }
    });

    $("#txtPass").on("keyup", function () {

        var pass = $("#txtPass").val().trim();
        if (pass !== "") {
            $("#lblpassError").addClass("d-none").text("");
        }
        else {
            $("#lblpassError").text("Please enter Password.").removeClass("d-none");

        }
    });


    /* =======================
      LOGIN
   ======================= */
    $("#btnSubmitLogin").click(function () {

        var email = $("#txtEmail").val().trim();
        var pass = $("#txtPass").val().trim();
        var captcha = $("#txtCaptcha").val().trim();

        if (email === "" || pass === "" || (captcha === "" || captcha.length !== 5)) {
            if (email === "")
                $("#lblemailError").text("Please enter Username.").removeClass("d-none");
            if (pass === "")
                $("#lblpassError").text("Please enter Password.").removeClass("d-none");
            if (captcha === "" )
                $("#captchaErrorMsg").text("Please Enter Captcha").removeClass("d-none").addClass("d-flex");

            return;
        }


        $.post("/Login/VerifyCaptcha", { captcha: $("#txtCaptcha").val() })
            .done(valid => {

                if (valid) {
                    isCaptchaVerified = true;

                    $("#txtCaptcha").prop("disabled", true);
                    $("#captchaErrorMsg").addClass("d-none").removeClass("d-flex").text("");

                    $.ajax({
                        url: '/Login/Login',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            Email: $("#txtEmail").val(),
                            Password: $("#txtPass").val()
                        }),
                        success: function (res) {
                            if (res.result === 1) {
                                toastr.success("Login successful");
                                // ✅ RESET OTP ATTEMPTS FOR NEW SESSION
                                clearOtpAttempts();
                                otpAttempts = {};
                                qrGenerated = false;
                                currentStepIndex = authSteps.findIndex(s => s !== "captcha");
                                currentStepIndex !== -1 ? showCurrentStep() : redirectHome();
                            }
                            else {
                                toastr.error(res.message || "Invalid email or password");
                            }

                        },
                        error: function () {
                            toastr.error("Login failed. Please try again.");
                        }
                    });

                    saveAuthState({ screen: "login" });
                } else {
                    $("#captchaErrorMsg").text("Invalid Captcha").removeClass("d-none").addClass("d-flex");
                    // Auto hide error after 3 seconds
                    $("#txtCaptcha").val('');
                    refreshCaptcha();
                    return false;
                }
            });

       

    });

    /* =======================
       CAPTCHA
    ======================= */
    //$("#btnVerifyCaptcha").click(function () {


    //});

    /* =======================
       OTP VERIFY HANDLERS
    ======================= */
    $('#verifyBtn').click(() => verifyStaticOtp('email_verify'));
    $('#verifyBtnMobile').click(() => verifyStaticOtp('mobile_verify'));



    $('#verifyBtn2FA').click(function () {
        const otp = getOtpValue('two_factor');

        if (otp.length < 6) {
            toastr.warning("Please enter a 6-digit OTP");
            return;
        }

        $.post('/Login/VerifyTwoFactorCode', { otp })
            .done(res => {
                if (res.success) {
                    toastr.success("2FA verification successful");

                    otpAttempts['two_factor'] = 0;
                    saveOtpAttempts();
                    goToNextStep();


                    // ✅ RESET ATTEMPTS HERE
                    //otpConfigs['2fa'].resetAttempts();
                    //saveOtpAttempts();   // 🔥 ADD THIS LINE
                    //goToNextStep();
                } else {
                    resetOtp('two_factor');
                    handleOtpFailure('two_factor');
                }
            });
    });


    //$('#btnClear2FA').on('click', function () {

    //    if (!confirm("This will require scanning QR code again. Continue?")) {
    //        return;
    //    }

    //    $.ajax({
    //        url: '/Login/ResetTwoFactorForNewDevice',
    //        type: 'POST',
    //        success: function (response) {
    //            if (response.success) {
    //                // Redirect to login page


    //                load2FASetup();
    //                //window.location.href = '/Login';
    //            } else {
    //                alert(response.message || "Unable to reset 2FA");
    //            }
    //        },
    //        error: function () {
    //            alert("Something went wrong. Please try again.");
    //        }
    //    });
    //});


    $("#btnVeriyOtpScreen").on("click", function () {

        // Show main 2FA container
        showScreen("twoFactorQRContainer");

        //$('#twoFactorQRContainer').removeClass('d-none').addClass('d-flex');

        // Hide QR screen
        $('#QRGenerator').addClass('d-none');

        // Show OTP verify screen
        $('#twoFactorVerifyContainer').removeClass('d-none');

        //if (qrGenerated) {
        //    $('#btnShowQRAgain').removeClass('d-none');

        //    $('#btnClear2FA').addClass('d-none');

        //} else {
        //    $('#btnShowQRAgain').addClass('d-none');

        //    $('#btnClear2FA').removeClass('d-none');

        //}

        // Hide No QR message if visible
        $("#NoQrCode").addClass("d-none");



        // Focus first OTP input
        $('#twoFactorVerifyContainer input:first').focus();
        saveAuthState({ screen: "2fa_verify" });


    });

    $('#btnShowQRAgain').click(function () {
        if (!qrGenerated) return; // safety check

        // Hide OTP inputs
        $('#twoFactorVerifyContainer').addClass('d-none');

        // Show QR generator container
        $('#QRGenerator').removeClass('d-none');
        $('#twoFactorQRContainer').removeClass('d-none');
        $('#NoQrCode').addClass('d-none');
    });


    $('#btnRefreshCaptch').on('click', function () {
        refreshCaptcha();
    });
});
/* =======================
 SESSION STORAGE HELPERS
 ======================= */
function saveAuthState(extra = {}) {
    sessionStorage.setItem("authState", JSON.stringify({
        currentStepIndex,
        qrGenerated,
        ...extra
    }));
}

function getAuthState() {
    const state = sessionStorage.getItem("authState");
    return state ? JSON.parse(state) : null;
}

function clearAuthState() {
    sessionStorage.removeItem("authState");
}

function saveOtpAttempts() {
    sessionStorage.setItem("otpAttempts", JSON.stringify(otpAttempts));
    //sessionStorage.setItem("otpAttempts", JSON.stringify({
    //    email: emailOtpAttempts,
    //    mobile: mobileOtpAttempts,
    //    twoFactor: twoFactorOtpAttempts
    //}));
}

//function restoreOtpAttempts() {
//    const data = sessionStorage.getItem("otpAttempts");
//    if (!data) return;

//    const attempts = JSON.parse(data);
//    console.log(attempts);
//    emailOtpAttempts = attempts.email ?? 0;
//    mobileOtpAttempts = attempts.mobile ?? 0;
//    twoFactorOtpAttempts = attempts.twoFactor ?? 0;
//}


function restoreOtpAttempts() {
    const data = sessionStorage.getItem("otpAttempts");
    if (data) {
        otpAttempts = JSON.parse(data);
    }
    
}

function clearOtpAttempts() {
    sessionStorage.removeItem("otpAttempts");
}


restoreOtpAttempts();

/* =======================
   OTP CONFIG MAP
======================= */
//const otpConfigs = {
//    email: {
//        inputs: $('.otp-input'),
//        verifyBtn: $('#verifyBtn'),
//        attempts: () => emailOtpAttempts++,
//        resetAttempts: () => emailOtpAttempts = 0
//    },
//    mobile: {
//        inputs: $('.otp-input-mobile'),
//        verifyBtn: $('#verifyBtnMobile'),
//        attempts: () => mobileOtpAttempts++,
//        resetAttempts: () => mobileOtpAttempts = 0
//    },
//    '2fa': {
//        inputs: $('.otp-input-2FA'),
//        verifyBtn: $('#verifyBtn2FA'),
//        attempts: () => twoFactorOtpAttempts++,
//        resetAttempts: () => twoFactorOtpAttempts = 0
//    }
//};

const otpConfigs = {
    email_verify: {
        inputs: $('.otp-input'),
        verifyBtn: $('#verifyBtn')
    },
    mobile_verify: {
        inputs: $('.otp-input-mobile'),
        verifyBtn: $('#verifyBtnMobile')
    },
    two_factor: {
        inputs: $('.otp-input-2FA'),
        verifyBtn: $('#verifyBtn2FA')
    }
};


/* =======================
RESTORE STATE ON REFRESH
======================= */
const savedState = getAuthState();
if (savedState) {
    currentStepIndex = savedState.currentStepIndex ?? 0;
    qrGenerated = savedState.qrGenerated ?? false;
}
restoreOtpAttempts(); // ✅ add here





/* =======================
   RESTORE SCREEN
======================= */
function restoreScreen(state) {
    if (!state || !state.screen) {
        return;
    }

    switch (state.screen) {
        case "email":
            showScreen("emailOtpVerifyContainer");
            break;

        case "mobile":
            showScreen("mobieOtpVerifyContainer");
            break;

        case "2fa_qr":
            showScreen("twoFactorQRContainer");
            load2FASetup();
            break;

        case "2fa_verify":
            showScreen("twoFactorQRContainer");
            $("#QRGenerator").addClass("d-none");
            $("#twoFactorVerifyContainer").removeClass("d-none");
            break;

        //default:
        //    showScreen("loginContainer");
    }
}



/* =======================
   OTP INPUT HANDLER
======================= */
function initOtpInputs(config) {

    config.inputs.on('input', function () {
        const $this = $(this);
        const index = parseInt($this.data('index'));
        const value = $this.val();

        if (!/^\d*$/.test(value)) {
            $this.val('');
            return;
        }

        if (value && index < config.inputs.length - 1) {
            config.inputs.eq(index + 1).focus();
        }

        updateOtpStyles();
        checkOtpComplete();
    });

    config.inputs.on('keydown', function (e) {
        const index = parseInt($(this).data('index'));

        if (e.key === 'Backspace' && !this.value && index > 0) {
            config.inputs.eq(index - 1).val('').focus();
        }

        if (e.key === 'ArrowLeft' && index > 0)
            config.inputs.eq(index - 1).focus();

        if (e.key === 'ArrowRight' && index < config.inputs.length - 1)
            config.inputs.eq(index + 1).focus();
    });

    config.inputs.on('click', function () {
        this.select();
    });
}

Object.values(otpConfigs).forEach(initOtpInputs);


/* =======================
   SCREEN CONTROL
======================= */
function showCurrentStep() {

    const step = authSteps[currentStepIndex];
    

    

    // ✅ Reset attempts ONLY when entering step first time
    otpAttempts[step] ??= 0;
    saveOtpAttempts();

    hideAllScreens();


    if (step === "email_verify") {
        showScreen("emailOtpVerifyContainer");
        saveAuthState({ screen: "email" });
        onOtpSent("email_verify");   // ✅ IMPORTANT
    }

    if (step === "mobile_verify") {
        showScreen("mobieOtpVerifyContainer");
        saveAuthState({ screen: "mobile" });

        onOtpSent("mobile_verify");  // ✅ IMPORTANT

    }

    if (step === "two_factor") {
        showScreen("twoFactorQRContainer");
        load2FASetup();
        saveAuthState({ screen: "2fa_qr" });
    }
}

function goToNextStep() {
    currentStepIndex++;
    saveAuthState();
    currentStepIndex < authSteps.length ? showCurrentStep() : redirectHome();
}

/* =======================
   HELPERS
======================= */
function updateOtpStyles() {
    $('.otp-input, .otp-input-mobile, .otp-input-2FA')
        .toggleClass('filled', function () {
            return $(this).val() !== '';
        });
}

function checkOtpComplete() {
    Object.values(otpConfigs).forEach(cfg => {
        const incomplete = cfg.inputs.toArray().some(i => !i.value);
        cfg.verifyBtn.prop('disabled', incomplete);

        if (incomplete) {
            cfg.verifyBtn.attr("title", "Enter complete OTP");
        }
    });

    Object.values(otpConfigs).forEach(cfg => {
        const allFilled = cfg.inputs.toArray().every(i => i.value.trim() !== '');
        cfg.verifyBtn.prop('disabled', !allFilled);
    });
}


function getOtpValue(type) {
    if (!otpConfigs[type]) {
        console.error("Invalid OTP type:", type);
        return '';
    }


    let otp = '';
    
    otpConfigs[type].inputs.each(function () {
        otp += this.value;
    });
    return otp;
}

// Reset OTP inputs (invalid attempt)
function resetOtp(type) {
    otpConfigs[type].inputs.val('').removeClass('filled');
    otpConfigs[type].verifyBtn.prop('disabled', true); // disable verify button
    otpConfigs[type].inputs.first().focus();
}

function handleOtpFailure(type) {

    otpAttempts[type]++;
    saveOtpAttempts();

    //const attempts = otpConfigs[type].attempts();
    //saveOtpAttempts(); // 🔥 persist attempts
    const maxAttempts = otpSettings[type]?.maxAttempts ?? 0;
    console.log(maxAttempts);
    const remaining = maxAttempts - otpAttempts[type];

    if (otpAttempts[type] >= maxAttempts) {
    
        toastr.error("Too many failed attempts. Redirecting...");
        clearAuthState();
        setTimeout(() => window.location.href = "/Login", 1500);
        

    } else {
        toastr.warning(
            `
    <div>
        <strong>Invalid ${type} OTP</strong><br>
        Attempts left: <strong>${remaining}</strong>
    </div>
    `
        );

    }
}

function refreshCaptcha() {
    $("#captchaImg").attr("src", "/Login/Generate?" + Date.now());
}

function hideAllScreens() {
    $('.auth-screen').removeClass('active');
}

function showScreen(id) {
    hideAllScreens();
    $('#' + id).addClass('active');
}

function redirectHome() {
    clearAuthState();
    clearOtpAttempts(); // ✅ important
    window.location.href = "/Home/Home";
}

function verifyStaticOtp(type) {
    const otp = getOtpValue(type);

    if (otp === "123456") {
        toastr.success(`${type.toUpperCase()} OTP verified`);
        // ✅ STOP resend timer for THIS OTP
        stopResendTimer(type);
        otpAttempts[type] = 0;
        saveOtpAttempts();

        goToNextStep();

        
    } else {
        resetOtp(type);
        handleOtpFailure(type);
    }

}

function initAuthSettings(authSettings) {
    console.log(authSettings);
    

    authSettings.forEach(x => {
        const code = (x.authCode || '').toLowerCase();
        otpSettings[code] = {
            maxAttempts: x.otpattempt,
            resendAfterMin: x.otpresetTime
        };

        otpAttempts[code] = 0;
    });

    Object.keys(otpSettings).forEach(key => {
        console.log(
            key,
            otpSettings[key].maxAttempts,
            otpSettings[key].resendAfterMin
        );
    });
    Object.keys(otpAttempts).forEach(key => {
        console.log(
            key,
            otpSettings[key].maxAttempts,
            otpSettings[key].resendAfterMin
        );
    });


    authSteps = [];

    STEP_ORDER.forEach(step => {
        if (authSettings.some(a =>
            (a.authCode || "").toLowerCase() === step && a.isEnabled)) {
            authSteps.push(step);
        }
    });

    // CAPTCHA HANDLING
    if (authSteps.includes("captcha")) {
        $("#authCaptchaDiv").removeClass("d-none");
        //$("#btnSubmitLogin").prop("disabled", true);
        isCaptchaVerified = false;
    } else {
        $("#authCaptchaDiv").addClass("d-none");
        //$("#btnSubmitLogin").prop("disabled", false);
        isCaptchaVerified = true;
    }

    // 🔁 RESTORE SESSION OR FIRST LOAD
    const state = getAuthState();
    if (state && state.screen) {
        currentStepIndex = state.currentStepIndex ?? 0;
        qrGenerated = state.qrGenerated ?? false;
        restoreScreen(state);
    } else {
        showScreen("loginContainer");
    }

    restoreOtpAttempts();
}


function load2FASetup() {


    $.ajax({
        url: "/Login/GenerateTwoFactorQR", // adjust controller path
        type: "GET",
        success: function (response) {


            if (response.success == true && response.isEnabled == false) {
                showScreen("twoFactorQRContainer");
                $('#twoFactorVerifyContainer').addClass('d-none');
                // Bind QR image
                $("#QRCODE").html(
                    `<img src="${response.qrCode}" alt="2FA QR Code" />`
                );

                // Show QR section
                $("#QRGenerator").removeClass("d-none");
                $(".no-qr-message").addClass("d-none");

                // Set flag: QR generated first time
                qrGenerated = true;


            } else {
                // Show main 2FA container
                /*$('#twoFactorQRContainer').removeClass('d-none').addClass('d-flex');*/
                qrGenerated = false;

                showScreen("twoFactorQRContainer");


                // Hide QR screen
                $('#QRGenerator').addClass('d-none');



                //if (qrGenerated) {
                //    $('#btnShowQRAgain').removeClass('d-none');

                //    $('#btnClear2FA').addClass('d-none');

                //} else {
                //    $('#btnShowQRAgain').addClass('d-none');

                //    $('#btnClear2FA').removeClass('d-none');

                //}


                // Show OTP verify screen
                $('#twoFactorVerifyContainer').removeClass('d-none');



                // Hide No QR message if visible
                $("#NoQrCode").addClass("d-none");

                // Focus first OTP input
                $('#twoFactorVerifyContainer input:first').focus();
            }
        },
        error: function () {
            toastr.error("Unable to load 2FA QR code. Please try again.");
            showNoQr();
        }

    });
}

function startResendTimer(type) {

    type = type.toLowerCase();

    const resendMin = otpSettings[type]?.resendAfterMin;
    if (!resendMin) return;

    const waitMs = resendMin * 60 * 1000;
    const enableAt = Date.now() + waitMs;

    resendTimers[type] = enableAt;

    sessionStorage.setItem("resendTimers", JSON.stringify(resendTimers));

    disableResendLink(type);
    updateResendCountdown(type);
}

function disableResendLink(type) {
    $('#resendLink')
        .addClass('disabled')
        .text('Resend OTP');

    $('#resendLinkMobile')
        .addClass('disabled')
        .text('Resend OTP');
}

function enableResendLink(type) {
    $('#resendLink')
        .removeClass('disabled')
        .text('Resend OTP');

    $('#resendLinkMobile')
        .removeClass('disabled')
        .text('Resend OTP');
}

function updateResendCountdown(type) {

    const interval = setInterval(() => {

        const enableAt = resendTimers[type];
        if (!enableAt) return clearInterval(interval);

        const remaining = Math.max(0, enableAt - Date.now());

        if (remaining <= 0) {
            clearInterval(interval);
            enableResendLink(type);
            return;
        }

        const sec = Math.ceil(remaining / 1000);
        $('#resendLink').text(`Resend OTP in ${sec}s`);
        $('#resendLinkMobile').text(`Resend OTP in ${sec}s`);

    }, 1000);
}

function stopResendTimer(type) {
    type = type.toLowerCase();

    if (resendIntervals[type]) {
        clearInterval(resendIntervals[type]);
        delete resendIntervals[type];
    }

    delete resendTimers[type];
    sessionStorage.setItem("resendTimers", JSON.stringify(resendTimers));

    enableResendLink(type);
}


function restoreResendTimers() {

    const data = sessionStorage.getItem("resendTimers");
    if (!data) return;

    resendTimers = JSON.parse(data);

    Object.keys(resendTimers).forEach(type => {
        if (Date.now() < resendTimers[type]) {
            disableResendLink(type);
            updateResendCountdown(type);
        } else {
            enableResendLink(type);
        }
    });
}

function onOtpSent(type) {
    type = type.toLowerCase();

    // Reset OTP inputs
    resetOtp(type);

    // Start resend timer from DB
    startResendTimer(type);

    toastr.info(`OTP sent (${type.replace('_', ' ')})`);
}

$(document).ready(function () {

    $('#btnSubmitLogin').click(function () {

        let email = $('#txtEmail').val().trim();
        let pass = $('#txtPass').val().trim();
        let captcha = $('#txtCaptcha').val().trim();

        if (!email) {
            $('#lblemailError').removeClass('d-none').text('Enter email');
            return;
        }
        if (!pass) {
            $('#lblpassError').removeClass('d-none').text('Enter password');
            return;
        }

        $.post('/verify-captcha', {
            captcha: captcha,
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function (valid) {

            if (!valid) {
                $('#captchaErrorMsg').removeClass('d-none').text('Invalid Captcha');
                refreshCaptcha();
                return;
            }

            $.ajax({
                url: '/login',
                type: 'POST',
                data: {
                    email: email,
                    password: pass,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                    if (res.result === 1) {
                        toastr.success('Login successful');
                        window.location.href = '/home';
                    } else {
                        toastr.error(res.message);
                    }
                }
            });
        });
    });
});






