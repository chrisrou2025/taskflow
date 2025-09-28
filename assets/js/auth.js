// Fonction pour afficher/masquer le mot de passe
function togglePassword(fieldId) {
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
 * Initialise la logique d'indicateur de force et de validation des mots de passe.
 */
function initPasswordLogic(passwordFieldId, confirmFieldId) {
    const passwordField = document.getElementById(passwordFieldId);
    const confirmPasswordField = document.getElementById(confirmFieldId);

    // Les IDs de la barre et du texte d'aide sont les mêmes pour toutes les pages.
    const strengthBar = document.getElementById('password_strength');
    const passwordHelp = document.getElementById('password_help');
    const confirmHelp = document.getElementById('confirm_help');

    // Le bloc s'exécute uniquement si les éléments HTML requis sont présents
    if (passwordField && confirmPasswordField && strengthBar && passwordHelp && confirmHelp) {

        // Indicateur de force du mot de passe (Événement 'input')
        passwordField.addEventListener('input', function () {
            const password = this.value;
            let strength = 0;

            // Critères de force (plus exigeants)
            if (password.length >= 6) strength++; // Critère 1: 6+ chars
            if (password.length >= 8) strength++; // Critère 2: 8+ chars
            if (/[A-Z]/.test(password)) strength++; // Critère 3: Majuscule
            if (/[a-z]/.test(password)) strength++; // Critère 4: Minuscule
            if (/[0-9]/.test(password)) strength++; // Critère 5: Chiffre
            if (/[^A-Za-z0-9]/.test(password)) strength++; // Critère 6: Symbole

            const percentage = (strength / 6) * 100;
            let color = '';
            let message = '';

            // Nouveaux seuils : 5/6 pour être fort
            if (strength < 3) {
                color = 'bg-danger';
                message = 'Mot de passe faible';
            } else if (strength < 5) {
                color = 'bg-warning';
                message = 'Mot de passe moyen';
            } else {
                color = 'bg-success';
                message = 'Mot de passe fort';
            }

            strengthBar.className = `progress-bar ${color}`;
            strengthBar.style.width = percentage + '%';
            passwordHelp.textContent = password.length === 0 ? 'Minimum 6 caractères requis' : message;

            // Mise à jour de la vérification de confirmation
            checkConfirmPassword(passwordField.value, confirmPasswordField.value, confirmHelp);
        });

        // Vérification de la confirmation du mot de passe
        confirmPasswordField.addEventListener('input', function () {
            checkConfirmPassword(passwordField.value, this.value, confirmHelp);
        });

        // Fonction réutilisable pour la vérification de confirmation
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
    }
}

document.addEventListener('DOMContentLoaded', function () {

    // PARTIE 1 : Vider les champs sur la page de connexion (aucune modification)
    const emailField = document.getElementById('inputEmail');
    const passwordFieldLogin = document.getElementById('inputPassword');
    const rememberField = document.getElementById('remember_me');

    if (emailField && passwordFieldLogin && rememberField) {
        emailField.value = '';
        passwordFieldLogin.value = '';
        rememberField.checked = false;
    }

    // PARTIE 2 : Initialisation de la logique de mot de passe unifiée

    let passwordFieldId = null;
    let confirmFieldId = null;

    // === DÉTECTION 1 : Page Register (ID dynamique Symfony) ===
    // On cherche un input de type password dont l'ID se termine par _first (convention Symfony RepeatedType)
    // Cela correspond à registrationForm.plainPassword.first
    const regPasswordInput = document.querySelector('input[type="password"][id$="_first"]');

    // On vérifie qu'il s'agit bien d'un champ d'inscription (l'ID doit être dans le formulaire)
    if (regPasswordInput && regPasswordInput.closest('form').classList.contains('form-container')) {
        passwordFieldId = regPasswordInput.id;
        // L'ID de confirmation est le même avec _second à la fin
        confirmFieldId = regPasswordInput.id.replace('_first', '_second');
    }
    // === DÉTECTION 2 : Page Changer Mot de Passe (ID statique #new_password) ===
    else if (document.getElementById('new_password')) {
        passwordFieldId = 'new_password';
        confirmFieldId = 'confirm_password';
    }
    // === DÉTECTION 3 : Page Réinitialiser Mot de Passe (ID statique #password) ===
    else if (document.getElementById('password')) {
        passwordFieldId = 'password';
        confirmFieldId = 'confirm_password';
    }

    // Finalisation : Si nous avons trouvé les IDs, nous initialisons la logique
    if (passwordFieldId && document.getElementById(confirmFieldId)) {
        initPasswordLogic(passwordFieldId, confirmFieldId);
    }
});

// Fonctionnalités pour la page de changement de mot de passe
function initChangePasswordPage() {
    const newPasswordField = document.getElementById('new_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    // const strengthBar, passwordHelp, confirmHelp ne sont plus nécessaires ici.
    const currentPasswordField = document.getElementById('current_password'); // Nécessaire pour la validation

    if (!newPasswordField || !confirmPasswordField || !currentPasswordField) return;

    // Validation du formulaire de changement de mot de passe (spécifique à user.js)
    const changePasswordForm = document.querySelector('.form-profile');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function (e) {
            const currentPassword = currentPasswordField.value;
            const newPassword = newPasswordField.value;
            const confirmPassword = confirmPasswordField.value;

            if (!currentPassword) {
                e.preventDefault();
                alert('Veuillez saisir votre mot de passe actuel.');
                return false;
            }

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return false;
            }

            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Le nouveau mot de passe doit contenir au moins 6 caractères.');
                return false;
            }
        });
    }
}

// Fonctionnalités pour la page de suppression de compte (laisser intact)
function initDeleteAccountPage() {
    const confirmationText = document.getElementById('confirmation_text');
    const passwordConfirmation = document.getElementById('password_confirmation');
    const finalConfirmation = document.getElementById('final_confirmation');
    const deleteButton = document.getElementById('delete_button');

    if (!confirmationText || !passwordConfirmation || !finalConfirmation || !deleteButton) return;

    function checkFormValid() {
        const isTextCorrect = confirmationText.value === 'SUPPRIMER';
        const isPasswordFilled = passwordConfirmation.value.length > 0;
        const isCheckboxChecked = finalConfirmation.checked;

        deleteButton.disabled = !(isTextCorrect && isPasswordFilled && isCheckboxChecked);
    }

    confirmationText.addEventListener('input', checkFormValid);
    passwordConfirmation.addEventListener('input', checkFormValid);
    finalConfirmation.addEventListener('change', checkFormValid);

    const deleteForm = document.querySelector('.form-delete');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function (e) {
            if (confirmationText.value !== 'SUPPRIMER' || !passwordConfirmation.value || !finalConfirmation.checked) {
                e.preventDefault();
                alert('Veuillez remplir correctement tous les champs de confirmation.');
                return false;
            }

            const confirmed = confirm(
                'ATTENTION : Vous êtes sur le point de supprimer définitivement votre compte.\n\n' +
                'Cette action ne peut pas être annulée.\n\n' +
                'Êtes-vous absolument sûr(e) de vouloir continuer ?'
            );

            if (!confirmed) {
                e.preventDefault();
                return false;
            }
        });
    }
}

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelector('.change-password-page')) {
        initChangePasswordPage();
    }

    if (document.querySelector('.delete-account-page')) {
        initDeleteAccountPage();
    }
});