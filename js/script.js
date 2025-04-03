document.addEventListener("DOMContentLoaded", () => {
    const otpProfileIcon = document.querySelector(".otp .profile-icon");
    if (otpProfileIcon) {
        otpProfileIcon.remove();
    }
    const eyeIcons = document.querySelectorAll(".pass-eye i"); // Select all eye icons

    eyeIcons.forEach(icon => {
        icon.addEventListener("click", () => {
            // Get the closest input field
            const passInput = icon.closest(".form-box").querySelector("input");

            if (passInput.type === "password") {
                passInput.type = "text"; // Show password
                icon.classList.replace("fa-eye-slash", "fa-eye"); // Change icon
            } else {
                passInput.type = "password"; // Hide password
                icon.classList.replace("fa-eye", "fa-eye-slash"); // Change icon
            }
        });
    });

    const passInput = document.getElementById("password");
    const confirmPassInput = document.getElementById("confirm_password");

    if (passInput && confirmPassInput) {
        const levels = document.querySelectorAll(".verify-pass div");
        const passCheck = document.getElementById("pass_check");

        // clear pass strength values on window load
        window.addEventListener("load", () => {
            levels.forEach(level => {
                level.classList.remove("strong0", "strong1", "strong2", "strong3");
            });
        });

        // show strength value to user in real time
        passInput.addEventListener("input", () => {
            const passValue = passInput.value;
            let strength = 0;
            if (passValue.length > 2) strength = 0; //short
            if (passValue.length > 4) strength = 1; //weak
            if (passValue.length > 7 && /[A-Z]/.test(passValue)) strength = 2; //good - should contain a captial letter
            if (passValue.length > 9 && /[\W_]/.test(passValue)) strength = 3; //strong - should contain a special character

            //adding strength levels dynamically
            levels.forEach((level, index) => {
                if (index <= strength) {
                    level.classList.add(`strong${strength}`);
                } else {
                    level.classList.remove("strong0", "strong1", "strong2", "strong3");
                }
            });

            //update text
            const strengthText = ["short", "weak", "good", "strong"];
            passCheck.innerText = strengthText[strength];
            passCheck.style.color = strength < 2 ? "red" : strength < 3 ? "orange" : "green";
        });

        // Check password similarity in real time
        function validatePasswords() {
            const passValue = passInput.value;
            const confirmPassValue = confirmPassInput.value;
            confirmPassInput.addEventListener("focus", () => {
                if (confirmPassInput.value.length === 0) {
                    confirmPassInput.style.outline = "1px solid #000000"; 
                }
            });
            confirmPassInput.addEventListener("blur", () => {
                confirmPassInput.style.outline = "none"; // Remove outline when focus is lost (blur)
            });
            confirmPassInput.addEventListener("input", () => {
                if (confirmPassInput.value.length > 0) {
                    confirmPassInput.style.outline = "none"; 
                } else {
                    confirmPassInput.style.outline = "1px solid #000000";
                }
            });
            if (confirmPassValue === "" ) {
                confirmPassInput.style.border = "1px solid #CFCFCF";
            } else if (passValue === confirmPassValue) {
                passInput.style.border = "1px solid green";
                confirmPassInput.style.border = "1px solid green";
            } else {
                passInput.style.border = "1px solid #CFCFCF";
                confirmPassInput.style.border = "1px solid red";
            }
        };
        confirmPassInput.addEventListener("input", validatePasswords);
        passInput.addEventListener("input", validatePasswords);
    }

    // Global document click handler to close popups
    document.addEventListener("click", function(event) {
        // Close upload form if it's open and click is outside
        const uploadForm = document.querySelector(".upload-form");
        const addFile = document.getElementById("add_file");
        if (uploadForm && uploadForm.style.display === "block" &&
            addFile && !addFile.contains(event.target) && 
            !uploadForm.contains(event.target)) {
            uploadForm.style.display = "none";
        }
    });

    // upload file popup
    const addFile = document.getElementById("add_file");
    const uploadForm = document.querySelector(".upload-form");
    
    // Check if we're on the dashboard page with these elements
    if (addFile && uploadForm) {
        if (typeof hasUploadErrors !== 'undefined' && hasUploadErrors === true) {
            uploadForm.style.display = "block";
        } else {
            uploadForm.style.display = "none";
        }

        // Toggle form visibility when Add File is clicked
        addFile.addEventListener("click", function(event) {
            event.stopPropagation();
            uploadForm.style.display = "block";
        });
        
        // Prevent form closing when clicking inside the form
        uploadForm.addEventListener("click", function(event) {
            event.stopPropagation();
        });
    }
    // resend otp loading overlay
    const resendButton = document.getElementById("resendOtp");
    if (resendButton) {
        resendButton.addEventListener("click", resendOtp);
    }
});

function resendOtp(event) {
    showLoadingOverlay("Resending code...");
    const form = document.getElementById("new_otp");
    if (form) {
        form.submit(); 
    } else {
        console.error("Could not find the form 'new_otp' to submit.");
        hideLoadingOverlay();
    }
}

// Remove messages after 5 seconds
setTimeout(function () {
    let successMessage = document.getElementById('successMessage');
    if (successMessage) {
        successMessage.style.transition = 'opacity 1s';
        successMessage.style.opacity = '0';
        setTimeout(() => successMessage.remove(), 1000);
    }
    let errorMessages = document.getElementById('errorMessages');
    if (errorMessages) {
        errorMessages.style.transition = 'opacity 1s';
        errorMessages.style.opacity = '0';
        setTimeout(() => errorMessages.remove(), 1000);
    }
}, 5000); // 5 seconds