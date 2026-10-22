function notificationToastCustom(type, message, title) {
    if (type == 'success') {
        toastr.success(message, title, toasterOption());
    }
    else if (type == 'error') {
        toastr.error(message, title, toasterOption());
    }
    else if (type == 'info') {
        toastr.info(message, title, toasterOption());
    }
    else if (type == 'warning') {
        toastr.warning(message, title, toasterOption());
    }
}
function toasterOption() {
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000,
        extendedTimeOut: 1000,
        preventDuplicates: true,
        newestOnTop: true
    };

}