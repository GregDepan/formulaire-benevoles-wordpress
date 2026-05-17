/**
 * Public scripts for Formulaire Bénévoles
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        const selectedSlots = new Map(); // stand_id -> {creneau_id, start, end}
        const creneauGroups = new Map(); // creneau_id -> exclusion_group
        
        // Handle creneau checkbox changes
        $('.fb-creneau-checkbox').on('change', function() {
            const checkbox = $(this);
            const creneauId = checkbox.val();
            const standId = checkbox.data('stand-id');
            const startTime = checkbox.data('start');
            const endTime = checkbox.data('end');
            const exclusionGroup = checkbox.data('exclusion-group') || '';
            
            // Store the group for this creneau
            if (exclusionGroup) {
                creneauGroups.set(creneauId, exclusionGroup);
            }
            
            if (checkbox.is(':checked')) {
                // Check for time conflicts with other stands
                const conflict = checkTimeConflict(startTime, endTime, standId);
                
                if (conflict) {
                    checkbox.prop('checked', false);
                    alert(fbPublic.strings.conflictError + '\\n\\nConflit avec: ' + conflict);
                    return;
                }
                
                // Check for exclusion group conflicts
                if (exclusionGroup) {
                    const groupConflict = checkExclusionGroupConflict(creneauId, exclusionGroup);
                    if (groupConflict) {
                        checkbox.prop('checked', false);
                        alert('⚠️ Conflit de créneau\\n\\nVous ne pouvez pas choisir ce créneau car il appartient au groupe "' + exclusionGroup + '" et vous avez déjà sélectionné "' + groupConflict + '" qui est dans le même groupe.');
                        return;
                    }
                }
                
                // Store selected slot
                if (!selectedSlots.has(standId)) {
                    selectedSlots.set(standId, []);
                }
                selectedSlots.get(standId).push({
                    creneau_id: creneauId,
                    start: startTime,
                    end: endTime
                });
            } else {
                // Remove from selected slots
                if (selectedSlots.has(standId)) {
                    const slots = selectedSlots.get(standId);
                    const index = slots.findIndex(s => s.creneau_id === creneauId);
                    if (index > -1) {
                        slots.splice(index, 1);
                    }
                    if (slots.length === 0) {
                        selectedSlots.delete(standId);
                    }
                }
            }
        });
        
        // Form submission
        $('#fb-inscription-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = $('#fb-submit-btn');
            const messageDiv = $('#fb-form-message');
            
            // Validate at least one slot selected
            if (selectedSlots.size === 0) {
                showMessage('Veuillez sélectionner au moins un créneau', 'error');
                return;
            }
            
            // Validate required fields
            const nom = $('#fb-nom').val().trim();
            const prenom = $('#fb-prenom').val().trim();
            const email = $('#fb-email').val().trim();
            
            if (!nom || !prenom || !email) {
                showMessage('Veuillez remplir tous les champs obligatoires', 'error');
                return;
            }
            
            if (!isValidEmail(email)) {
                showMessage(fbPublic.strings.invalidEmail, 'error');
                return;
            }
            
            // Prepare data
            const creneaux = [];
            selectedSlots.forEach((slots) => {
                slots.forEach(slot => creneaux.push(slot.creneau_id));
            });
            
            const data = {
                action: 'fb_submit_inscription',
                nonce: form.find('input[name="nonce"]').val(),
                evenement_id: form.find('input[name="evenement_id"]').val(),
                nom: nom,
                prenom: prenom,
                email: email,
                telephone: $('#fb-telephone').val().trim(),
                create_account: $('#fb-create-account').is(':checked') ? '1' : '0',
                creneaux: creneaux
            };
            
            // Submit
            submitBtn.prop('disabled', true);
            submitBtn.find('.btn-text').hide();
            submitBtn.find('.btn-loading').show();
            
            $.ajax({
                url: fbPublic.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        showMessage(fbPublic.strings.success, 'success');
                        form[0].reset();
                        selectedSlots.clear();
                        $('.fb-creneau-checkbox').prop('checked', false);
                    } else {
                        let errorMsg = response.data.message || fbPublic.strings.error;
                        
                        if (response.data.message && response.data.message.includes('déjà inscrit')) {
                            errorMsg = fbPublic.strings.alreadyRegistered;
                        }
                        
                        showMessage(errorMsg, 'error');
                    }
                },
                error: function() {
                    showMessage(fbPublic.strings.error, 'error');
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    submitBtn.find('.btn-text').show();
                    submitBtn.find('.btn-loading').hide();
                }
            });
        });
        
        // Check for time conflicts
        function checkTimeConflict(newStart, newEnd, newStandId) {
            for (const [standId, slots] of selectedSlots.entries()) {
                if (standId === newStandId) continue; // Same stand, different slot is OK
                
                for (const slot of slots) {
                    if (timesOverlap(newStart, newEnd, slot.start, slot.end)) {
                        return slot.start + ' - ' + slot.end;
                    }
                }
            }
            return null;
        }
        
        // Check for exclusion group conflicts
        function checkExclusionGroupConflict(newCreneauId, newGroup) {
            for (const [creneauId, group] of creneauGroups.entries()) {
                if (creneauId === newCreneauId) continue;
                if (group === newGroup) {
                    // Find the title of the conflicting creneau
                    const checkbox = $('.fb-creneau-checkbox[value="' + creneauId + '"]');
                    return checkbox.closest('.fb-creneau-item').find('.fb-creneau-label').text().trim();
                }
            }
            return null;
        }
        
        // Check if two time ranges overlap
        function timesOverlap(start1, end1, start2, end2) {
            const s1 = timeToMinutes(start1);
            const e1 = timeToMinutes(end1);
            const s2 = timeToMinutes(start2);
            const e2 = timeToMinutes(end2);
            
            return (s1 < e2 && s2 < e1);
        }
        
        // Convert HH:MM to minutes
        function timeToMinutes(time) {
            const [hours, minutes] = time.split(':').map(Number);
            return hours * 60 + minutes;
        }
        
        // Validate email
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        // Show message
        function showMessage(text, type) {
            const messageDiv = $('#fb-form-message');
            messageDiv.text(text);
            messageDiv.removeClass('success error').addClass(type);
            messageDiv.fadeIn();
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    messageDiv.fadeOut();
                }, 5000);
            }
        }
        
        // Profile page login
        $('#fb-profile-login-form').on('submit', function(e) {
            e.preventDefault();
            
            const email = $('#fb-profile-email').val().trim();
            
            if (!email || !isValidEmail(email)) {
                alert('Veuillez entrer un email valide');
                return;
            }
            
            $.ajax({
                url: fbPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fb_get_profile',
                    nonce: fbPublic.nonce,
                    email: email
                },
                success: function(response) {
                    if (response.success) {
                        // Store token and show reservations
                        localStorage.setItem('fb_token', response.data.token);
                        localStorage.setItem('fb_email', email);
                        location.reload();
                    } else {
                        alert(response.data.message || 'Aucune réservation trouvée');
                    }
                }
            });
        });
        
        // Cancel reservation
        $(document).on('click', '.fb-cancel-reservation', function() {
            if (!confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) return;
            
            const reservationId = $(this).data('reservation-id');
            const token = $(this).data('token');
            
            $.ajax({
                url: fbPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fb_cancel_reservation',
                    nonce: fbPublic.nonce,
                    inscription_id: reservationId,
                    token: token
                },
                success: function(response) {
                    if (response.success) {
                        alert('Réservation annulée');
                        location.reload();
                    } else {
                        alert(response.data.message || 'Erreur lors de l\'annulation');
                    }
                }
            });
        });
        
        // Logout from profile
        $('#fb-profile-logout').on('click', function() {
            localStorage.removeItem('fb_token');
            localStorage.removeItem('fb_email');
            location.reload();
        });
    });
    
})(jQuery);
