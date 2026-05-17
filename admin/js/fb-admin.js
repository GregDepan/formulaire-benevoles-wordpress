/**
 * Admin scripts for Formulaire Bénévoles
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Add stand button
        $('#fb-add-stand-btn').on('click', function() {
            $('#fb-add-stand-modal').fadeIn();
        });
        
        // Cancel add stand
        $('#fb-stand-cancel').on('click', function() {
            $('#fb-add-stand-modal').fadeOut();
            resetStandForm();
        });
        
        // Save stand
        $('#fb-stand-save').on('click', function() {
            const evenementId = $('#fb-modal-evenement-id').val();
            const nom = $('#fb-stand-nom').val().trim();
            const quota = $('#fb-stand-quota').val();
            const couleur = $('#fb-stand-couleur').val();
            const description = $('#fb-stand-description').val().trim();
            
            if (!nom) {
                alert('Le nom du stand est obligatoire');
                return;
            }
            
            $.ajax({
                url: fbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fb_add_stand',
                    nonce: fbAdmin.nonce,
                    evenement_id: evenementId,
                    nom: nom,
                    quota: quota,
                    couleur: couleur,
                    description: description
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Erreur lors de l\'ajout du stand');
                }
            });
        });
        
        // Delete stand
        $(document).on('click', '.fb-delete-stand', function() {
            if (!confirm(fbAdmin.strings.confirmDelete)) return;
            
            const standId = $(this).data('stand-id');
            
            $.ajax({
                url: fbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fb_delete_stand',
                    nonce: fbAdmin.nonce,
                    stand_id: standId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + response.data.message);
                    }
                }
            });
        });
        
        // Add creneau button
        $('#fb-add-creneau-btn').on('click', function() {
            $('#fb-add-creneau-modal').fadeIn();
        });
        
        // Cancel add creneau
        $('#fb-creneau-cancel').on('click', function() {
            $('#fb-add-creneau-modal').fadeOut();
            resetCreneauForm();
        });
        
        // Save creneau
        $('#fb-creneau-save').on('click', function() {
            const standId = $('#fb-modal-stand-id').val();
            const heureDebut = $('#fb-creneau-heure-debut').val();
            const heureFin = $('#fb-creneau-heure-fin').val();
            const quotaSpecifique = $('#fb-creneau-quota').val();
            
            if (!heureDebut || !heureFin) {
                alert('Les heures de début et de fin sont obligatoires');
                return;
            }
            
            $.ajax({
                url: fbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fb_add_creneau',
                    nonce: fbAdmin.nonce,
                    stand_id: standId,
                    heure_debut: heureDebut,
                    heure_fin: heureFin,
                    quota_specifique: quotaSpecifique
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Erreur lors de l\'ajout du créneau');
                }
            });
        });
        
        // Delete creneau
        $(document).on('click', '.fb-delete-creneau', function() {
            if (!confirm(fbAdmin.strings.confirmDelete)) return;
            
            const creneauId = $(this).data('creneau-id');
            
            $.ajax({
                url: fbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fb_delete_creneau',
                    nonce: fbAdmin.nonce,
                    creneau_id: creneauId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + response.data.message);
                    }
                }
            });
        });
        
        // Clone event
        $('#fb-clone-event-btn').on('click', function() {
            if (!confirm(fbAdmin.strings.confirmClone)) return;
            
            const eventId = $(this).data('event-id');
            const newTitle = prompt('Nom du nouvel événement :');
            
            if (!newTitle) return;
            
            $.ajax({
                url: fbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fb_clone_event',
                    nonce: fbAdmin.nonce,
                    source_id: eventId,
                    new_title: newTitle
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        window.location.href = response.data.edit_url;
                    } else {
                        alert('Erreur: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Erreur lors du clonage');
                }
            });
        });
        
        // Manage creneaux button
        $(document).on('click', '.fb-manage-creneaux', function() {
            const standId = $(this).data('stand-id');
            // Redirect to stand edit page
            window.location.href = '/wp-admin/post.php?post=' + standId + '&action=edit';
        });
        
        function resetStandForm() {
            $('#fb-stand-nom').val('');
            $('#fb-stand-quota').val('5');
            $('#fb-stand-couleur').val('#3498db');
            $('#fb-stand-description').val('');
        }
        
        function resetCreneauForm() {
            $('#fb-creneau-heure-debut').val('');
            $('#fb-creneau-heure-fin').val('');
            $('#fb-creneau-quota').val('');
        }
        
        // Close modal on outside click
        $('.fb-modal').on('click', function(e) {
            if ($(e.target).is('.fb-modal')) {
                $(this).fadeOut();
            }
        });
    });
    
})(jQuery);
