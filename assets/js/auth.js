/**
 * Module pour gérer toute la logique des formulaires d'authentification.
 * (Inscription, connexion, changement/réinitialisation de mot de passe)
 */

// Fonction attachée au window pour la rendre accessible depuis l'attribut onclick dans Twig
// Cette fonction doit rester dans le scope global pour les appels onclick
window.togglePassword = function(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');

    if (field && icon) {
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}

/**
 * Initialise la logique de validation de mot de passe
 */
function initPasswordLogic(passwordFieldId, confirmFieldId) {
    const passwordField = document.getElementById(passwordFieldId);
    const confirmPasswordField = document.getElementById(confirmFieldId);
    const strengthBar = document.getElementById('password_strength');
    const passwordHelp = document.getElementById('password_help');
    const confirmHelp = document.getElementById('confirm_help');

    if (passwordField && strengthBar && passwordHelp) {
        passwordField.addEventListener('input', function () {
            const password = this.value;
            let strength = 0;
            
            // Calcul de la force du mot de passe
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            const percentage = (strength / 6) * 100;
            let color = 'bg-danger';
            let message = 'Mot de passe faible';

            if (strength >= 5) {
                color = 'bg-success';
                message = 'Mot de passe fort';
            } else if (strength >= 3) {
                color = 'bg-warning';
                message = 'Mot de passe moyen';
            }

            strengthBar.className = `progress-bar ${color}`;
            strengthBar.style.width = percentage + '%';
            passwordHelp.textContent = password.length === 0 ? 'Minimum 6 caractères requis' : message;

            // Vérification de la correspondance avec le champ de confirmation
            if (confirmPasswordField && confirmHelp) {
                checkConfirmPassword(passwordField.value, confirmPasswordField.value, confirmHelp);
            }
        });
    }

    if (confirmPasswordField && confirmHelp && passwordField) {
        confirmPasswordField.addEventListener('input', function () {
            checkConfirmPassword(passwordField.value, this.value, confirmHelp);
        });
    }
}

/**
 * Vérifie la correspondance entre le mot de passe et sa confirmation
 */
function checkConfirmPassword(newPassword, confirmPassword, helpElement) {
    if (confirmPassword.length === 0) {
        helpElement.textContent = 'Les mots de passe doivent être identiques';
        helpElement.className = 'form-text';
    } else if (newPassword === confirmPassword) {
        helpElement.textContent = 'Les mots de passe correspondent !';
        helpElement.className = 'form-text text-success';
    } else {
        helpElement.textContent = 'Les mots de passe ne correspondent pas';
        helpElement.className = 'form-text text-danger';
    }
}

/**
 * Initialise la logique de suppression de compte
 */
function initDeleteAccountLogic() {
    const confirmationInput = document.getElementById('confirmation_text');
    const finalCheckbox = document.getElementById('final_confirmation');
    const deleteButton = document.getElementById('delete_button');

    if (!confirmationInput || !finalCheckbox || !deleteButton) {
        return;
    }

    // Fonction de validation
    function validateDeleteForm() {
        const isTextValid = confirmationInput.value.trim() === 'SUPPRIMER';
        const isCheckboxChecked = finalCheckbox.checked;
        
        deleteButton.disabled = !(isTextValid && isCheckboxChecked);
    }

    // Écouteurs d'événements
    confirmationInput.addEventListener('input', validateDeleteForm);
    finalCheckbox.addEventListener('change', validateDeleteForm);

    // Validation initiale
    validateDeleteForm();
}

/**
 * Fonction principale exportée, appelée par app.js
 */
export function initAuthPage() {
    console.log('Module auth.js initialisé');

    // PARTIE 1 : Vider les champs sur la page de connexion
    const emailField = document.getElementById('inputEmail');
    const passwordFieldLogin = document.getElementById('inputPassword');
    const rememberField = document.getElementById('remember_me');

    if (emailField && passwordFieldLogin && rememberField) {
        emailField.value = '';
        passwordFieldLogin.value = '';
        rememberField.checked = false;
    }

    // PARTIE 2 : Logique de mot de passe pour inscription, réinitialisation ET changement
    let passwordFieldId = null;
    let confirmFieldId = null;

    // Détection page d'inscription
    const regPasswordInput = document.querySelector('input[type="password"][id$="_first"]');
    if (regPasswordInput) {
        passwordFieldId = regPasswordInput.id;
        confirmFieldId = regPasswordInput.id.replace('_first', '_second');
    }
    // Détection page de changement de mot de passe
    else if (document.getElementById('new_password') && document.getElementById('confirm_password')) {
        passwordFieldId = 'new_password';
        confirmFieldId = 'confirm_password';
    }
    // Détection page réinitialisation
    else if (document.getElementById('password') && document.getElementById('confirm_password')) {
        passwordFieldId = 'password';
        confirmFieldId = 'confirm_password';
    }
    
    // Initialisation si les champs ont été trouvés
    if (passwordFieldId && confirmFieldId) {
        initPasswordLogic(passwordFieldId, confirmFieldId);
    }

    // PARTIE 3 : Logique de suppression de compte
    if (document.querySelector('.delete-account-page')) {
        initDeleteAccountLogic();
    }

    // PARTIE 4 : Validation des formulaires Bootstrap
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}