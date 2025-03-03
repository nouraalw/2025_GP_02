document.addEventListener("DOMContentLoaded", function () {
    const nextBtns = document.querySelectorAll(".next-step");
    const prevBtns = document.querySelectorAll(".prev-step");
    const submitBtn = document.querySelector(".btn[type='submit']");
    const step1s = document.querySelectorAll(".step-1");
    const step2s = document.querySelectorAll(".step-2");
    const steps = document.querySelectorAll(".progress-bar .step");

    let isEmailVerified = false; // Track email verification state

    // ✅ Function: Validate name input
    function validateName(nameField) {
        const namePattern = /^[A-Za-z]{2,}(?: [A-Za-z]+)*$/;
        const repeatedPattern = /^(.)\1{2,}$/;
        
        if (!namePattern.test(nameField.value) || repeatedPattern.test(nameField.value)) {
            nameField.style.borderColor = "red";
            nameField.nextElementSibling.style.display = "block";
            return false;
        } else {
            nameField.style.borderColor = "green";
            nameField.nextElementSibling.style.display = "none";
            return true;
        }
    }

    // ✅ Function: Validate Step 1 (Basic Information)
    function validateStep1(form) {
        let valid = true;
        const firstName = form.querySelector("#first-name");
        const lastName = form.querySelector("#last-name");
        const email = form.querySelector("#email");
        const password = form.querySelector("#password");

        if (!validateName(firstName)) valid = false;
        if (!validateName(lastName)) valid = false;

        if (!email.value.includes("@")) {
            email.style.borderColor = "red";
            email.nextElementSibling.style.display = "block";
            valid = false;
        } else {
            email.style.borderColor = "green";
            email.nextElementSibling.style.display = "none";
        }

        const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#$&]).{8,}$/;
        if (!password.value.match(passwordPattern)) {
            password.style.borderColor = "red";
            password.parentElement.nextElementSibling.style.display = "block";
            valid = false;
        } else {
            password.style.borderColor = "green";
            password.parentElement.nextElementSibling.style.display = "none";
        }

        return valid;
    }

    // ✅ Function: Validate Step 2 (File Uploads)
    function validateStep2(form) {
        let valid = true;
        const field = form.querySelector("#field");
        const experience = form.querySelector("#experience");
        const profilePicture = form.querySelector("#profile-picture");
        const cv = form.querySelector("#cv");
        const description = form.querySelector("#brief-description");

        if (field.value === "") valid = false;
        if (experience.value === "") valid = false;
        if (profilePicture.files.length === 0) valid = false;
        if (cv.files.length === 0) valid = false;
        if (description.value.trim() === "") valid = false;

        return valid;
    }

    // ✅ Step Navigation: Next Button Click
    nextBtns.forEach((btn, index) => {
        btn.addEventListener("click", function () {
            const form = btn.closest("form");
            if (!isEmailVerified) {
                alert("Please verify your email before proceeding.");
                return;
            }
            if (validateStep1(form)) {
                step1s[index].classList.add("hidden");
                step2s[index].classList.remove("hidden");

                steps[index * 2].classList.add("completed");
                steps[index * 2].innerHTML = "✓";
                steps[index * 2 + 1].classList.add("active");
            }
        });
    });

    // ✅ Step Navigation: Previous Button Click
    prevBtns.forEach((btn, index) => {
        btn.addEventListener("click", function () {
            step2s[index].classList.add("hidden");
            step1s[index].classList.remove("hidden");

            steps[index * 2].classList.remove("completed");
            steps[index * 2].innerHTML = "1";
            steps[index * 2 + 1].classList.remove("active");
        });
    });



    // ✅ Toggle Password Visibility
    function togglePassword() {
        let passwordField = document.getElementById("password");
        passwordField.type = passwordField.type === "password" ? "text" : "password";
    }
 
    

//

let modal = document.getElementById("confirmation-modal");
    let okButton = document.querySelector(".modal-btn");

    if (modal) {
        modal.style.display = "flex"; // Show the modal when triggered

        // Ensure button redirects on click
        okButton.addEventListener("click", function () {
            window.location.href = 'homepage.html'; // Redirect to login page
        });
    }







//
    // ✅ EMAIL & OTP Verification
    const emailInput = document.getElementById("email");
    const emailError = document.getElementById("email-error");
    const nextBtn = document.querySelector(".btn.next-step");

    // Create OTP Input Box (Hidden by Default)
    const verificationBox = document.createElement("div");
    verificationBox.innerHTML = `
        <label for="verification-code">Enter Verification Code:</label>
        <input type="text" id="verification-code" name="verification_code" required>
        <button type="button" id="verify-code">Verify</button>
        <p id="code-error" style="color: red; display: none;">Incorrect Code</p>
    `;
    emailInput.parentElement.appendChild(verificationBox);
    verificationBox.style.display = "none";
    nextBtn.disabled = true;

    // ✅ Check Email & Show OTP Box
    emailInput.addEventListener("blur", function () {
        let email = emailInput.value.trim();
        if (email.includes("@")) {
            fetch("Mentor_Registration.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "verify_email=1&email=" + encodeURIComponent(email)
            })
            .then(response => response.text())
            .then(data => {
                console.log("Server Response:", data);
                if (data === "exists") {
                    emailError.innerText = "This email is already registered";
                    emailError.style.display = "block";
                    verificationBox.style.display = "none";
                } else if (data === "code_sent") {
                    emailError.style.display = "none";
                    verificationBox.style.display = "block";
                }
            });
        }
    });

    // ✅ Handle OTP Verification
    document.getElementById("verify-code").addEventListener("click", function () {
        let code = document.getElementById("verification-code").value.trim();
        fetch("Mentor_Registration.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "verify_code=1&code=" + encodeURIComponent(code)
        })
        .then(response => response.text())
        .then(data => {
            console.log("OTP Verification Response:", data);
            if (data === "verified") {
                document.getElementById("code-error").style.display = "none";
                alert("Email verified successfully!");
                isEmailVerified = true; // Mark email as verified
                nextBtn.disabled = false;
            } else {
                document.getElementById("code-error").style.display = "block";
            }
        });
    });

    // ✅ Ensure Submit Button is Only Clickable After Validations
    submitBtn.addEventListener("click", function (event) {
        const form = submitBtn.closest("form");
        if (!validateStep2(form)) {
            event.preventDefault();
            alert("Please complete all required fields before submitting.");
        }
    });

});



//

document.addEventListener("DOMContentLoaded", function () {
    const phoneInput = document.getElementById("phone");
    const phoneError = document.getElementById("phone-error");

    // ✅ Check Phone Number Availability
    phoneInput.addEventListener("blur", function () {
        let phone = phoneInput.value.trim();
        if (phone === "") return;

        fetch("Mentor_Registration.php", {
           method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "check_availability=phone&phone=" + encodeURIComponent(phone)
        })
        .then(response => response.text())
        .then(data => {
            console.log("Phone Check Response:", data);
            if (data === "exists") {
                phoneError.innerText = "This phone number is already registered";
               phoneError.style.display = "block";
               phoneInput.style.borderColor = "red";
              } else {
               phoneError.style.display = "none";
                phoneInput.style.borderColor = "green";
           }
        })
        .catch(error => console.error("Error checking phone number:", error));
    });
});



//



    






    //


    document.addEventListener("DOMContentLoaded", function () {
        let modal = document.getElementById("confirmation-modal");
        let okButton = document.querySelector(".modal-btn");
    
        if (modal && okButton) {
            modal.style.display = "flex"; // Show modal if needed
    
            // ✅ Ensure button redirects on click
            okButton.addEventListener("click", function () {
                window.location.href = "homepage.html"; // Redirect to homepage
            });
        } else {
            console.error("❌ Modal or OK button not found in DOM.");
        }
    });
    

























