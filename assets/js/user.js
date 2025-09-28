// Les fonctions d'indicateur de force du mot de passe et de bascule (togglePassword)
// sont maintenant unifiées et gérées par auth.js.

// Fonctionnalités pour la page de changement de mot de passe
function initChangePasswordPage() {
    const newPasswordField = document.getElementById('new_password');
    const confirmPasswordField = document.getElementById('confirm_password');
    // const strengthBar, passwordHelp, confirmHelp ne sont plus nécessaires ici.
    const currentPasswordField = document.getElementById('current_password'); // Nécessaire pour la validation

    if (!newPasswordField || !confirmPasswordField || !currentPasswordField) return;

    // L'écouteur d'événement 'input' pour la force/confirmation est dans auth.js

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
            // Si la vérification de la force du mot de passe dans auth.js est critique,
            // vous pouvez ajouter ici une vérification basée sur la couleur de la barre de progression,
            // mais ce n'est généralement pas nécessaire si le back-end vérifie la force.
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