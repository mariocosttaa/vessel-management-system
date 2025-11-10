<?php

return [
    // Profile
    'Profile updated successfully.' => 'Profil mis à jour avec succès.',
    'Failed to update profile: :message' => 'Échec de la mise à jour du profil: :message',
    'Your account has been deleted successfully.' => 'Votre compte a été supprimé avec succès.',
    'Failed to delete account. Please try again.' => 'Échec de la suppression du compte. Veuillez réessayer.',
    'Language updated successfully.' => 'Langue mise à jour avec succès.',
    'Failed to update language.' => 'Échec de la mise à jour de la langue.',

    // Marea
    "Marea ':number' has been created successfully." => "Marea ':number' a été créée avec succès.",
    'Failed to create marea: :message' => 'Échec de la création de la marea: :message',
    "Marea ':number' has been updated successfully." => "Marea ':number' a été mise à jour avec succès.",
    'Failed to update marea: :message' => 'Échec de la mise à jour de la marea: :message',
    "Marea ':number' has been deleted successfully." => "Marea ':number' a été supprimée avec succès.",
    'Failed to delete marea: :message' => 'Échec de la suppression de la marea: :message',
    "Marea ':number' has been marked as at sea." => "Marea ':number' a été marquée comme en mer.",
    'Failed to mark marea as at sea: :message' => 'Échec du marquage de la marea comme en mer: :message',
    "Marea ':number' has been marked as returned." => "Marea ':number' a été marquée comme retournée.",
    'Failed to mark marea as returned: :message' => 'Échec du marquage de la marea comme retournée: :message',
    "Marea ':number' has been closed." => "Marea ':number' a été fermée.",
    'Failed to close marea: :message' => 'Échec de la fermeture de la marea: :message',
    "Marea ':number' has been cancelled." => "Marea ':number' a été annulée.",
    'Failed to cancel marea: :message' => 'Échec de l\'annulation de la marea: :message',
    'Transaction has been added to the marea.' => 'Transaction a été ajoutée à la marea.',
    'Failed to add transaction: :message' => 'Échec de l\'ajout de la transaction: :message',
    'Transaction has been removed from the marea.' => 'Transaction a été retirée de la marea.',
    'Failed to remove transaction: :message' => 'Échec du retrait de la transaction: :message',

    // Recycle Bin
    ":type ':name' has been restored successfully." => ":type ':name' a été restauré avec succès.",
    'Failed to restore item: :message' => 'Échec de la restauration de l\'élément: :message',
    ":type ':name' has been permanently deleted." => ":type ':name' a été supprimé définitivement.",
    'Failed to permanently delete item: :message' => 'Échec de la suppression définitive de l\'élément: :message',
    'Recycle bin has been emptied. :count item(s) have been permanently deleted.' => 'Corbeille vidée. :count élément(s) ont été supprimés définitivement.',
    'Failed to empty recycle bin: :message' => 'Échec du vidage de la corbeille: :message',

    // Transaction
    "Transaction ':number' has been created successfully." => "Transaction ':number' a été créée avec succès.",
    'Failed to create transaction: :message' => 'Échec de la création de la transaction: :message',
    "Transaction ':number' has been updated successfully." => "Transaction ':number' a été mise à jour avec succès.",
    'Failed to update transaction: :message' => 'Échec de la mise à jour de la transaction: :message',
    "Transaction ':number' has been deleted successfully." => "Transaction ':number' a été supprimée avec succès.",
    'Failed to delete transaction: :message' => 'Échec de la suppression de la transaction: :message',

    // Marea - Additional
    "Marea ':number' has been deleted successfully. :count transaction(s) associated with this marea have also been deleted." => "Marea ':number' a été supprimée avec succès. :count transaction(s) associée(s) à cette marea ont également été supprimées.",
    'Crew member has been added to the marea.' => 'Membre d\'équipage a été ajouté à la marea.',
    'Failed to add crew member: :message' => 'Échec de l\'ajout du membre d\'équipage: :message',
    'Crew member is already assigned to this marea.' => 'Membre d\'équipage déjà assigné à cette marea.',
    'Crew member has been removed from the marea.' => 'Membre d\'équipage a été retiré de la marea.',
    'Failed to remove crew member: :message' => 'Échec du retrait du membre d\'équipage: :message',
    'Quantity return has been added to the marea.' => 'Retour de quantité a été ajouté à la marea.',
    'Failed to add quantity return: :message' => 'Échec de l\'ajout du retour de quantité: :message',
    'Quantity return has been removed from the marea.' => 'Retour de quantité a été retiré de la marea.',
    'Failed to remove quantity return: :message' => 'Échec du retrait du retour de quantité: :message',
    'Distribution calculation override has been saved successfully.' => 'Substitution de calcul de distribution a été enregistrée avec succès.',
    'Failed to save distribution items: :message' => 'Échec de l\'enregistrement des éléments de distribution: :message',
    'Salary payment has been created successfully.' => 'Paiement de salaire a été créé avec succès.',
    'Failed to create salary payment: :message' => 'Échec de la création du paiement de salaire: :message',

    // Maintenance
    "Maintenance ':number' has been created successfully." => "Maintenance ':number' a été créée avec succès.",
    'Failed to create maintenance: :message' => 'Échec de la création de la maintenance: :message',
    "Maintenance ':number' has been updated successfully." => "Maintenance ':number' a été mise à jour avec succès.",
    'Failed to update maintenance: :message' => 'Échec de la mise à jour de la maintenance: :message',
    "Maintenance ':number' has been deleted successfully." => "Maintenance ':number' a été supprimée avec succès.",
    'Failed to delete maintenance: :message' => 'Échec de la suppression de la maintenance: :message',
    "Maintenance ':number' has been finalized." => "Maintenance ':number' a été finalisée.",
    'Failed to finalize maintenance: :message' => 'Échec de la finalisation de la maintenance: :message',
    'Transaction has been removed from the maintenance.' => 'Transaction a été retirée de la maintenance.',
    'Failed to remove transaction from maintenance: :message' => 'Échec du retrait de la transaction de la maintenance: :message',

    // Vessel
    "Vessel ':name' has been created successfully." => "Navire ':name' a été créé avec succès.",
    'Failed to create vessel. Please try again.' => 'Échec de la création du navire. Veuillez réessayer.',
    "Vessel ':name' has been updated successfully." => "Navire ':name' a été mis à jour avec succès.",
    'Failed to update vessel. Please try again.' => 'Échec de la mise à jour du navire. Veuillez réessayer.',
    "Cannot delete vessel ':name' because it has crew members assigned. Please reassign or remove crew members first." => "Impossible de supprimer le navire ':name' car il a des membres d\'équipage assignés. Veuillez réassigner ou supprimer les membres d\'équipage d\'abord.",
    "Cannot delete vessel ':name' because it has transactions. Please remove all transactions first." => "Impossible de supprimer le navire ':name' car il a des transactions. Veuillez supprimer toutes les transactions d\'abord.",
    "Vessel ':name' has been deleted successfully." => "Navire ':name' a été supprimé avec succès.",
    'Failed to delete vessel. Please try again.' => 'Échec de la suppression du navire. Veuillez réessayer.',

    // Supplier
    "Supplier ':name' has been created successfully." => "Fournisseur ':name' a été créé avec succès.",
    'Failed to create supplier. Please try again.' => 'Échec de la création du fournisseur. Veuillez réessayer.',
    "Supplier ':name' has been updated successfully." => "Fournisseur ':name' a été mis à jour avec succès.",
    'Failed to update supplier. Please try again.' => 'Échec de la mise à jour du fournisseur. Veuillez réessayer.',
    "Cannot delete supplier ':name' because they have transactions. Please remove all transactions first." => "Impossible de supprimer le fournisseur ':name' car il a des transactions. Veuillez supprimer toutes les transactions d\'abord.",
    "Supplier ':name' has been deleted successfully." => "Fournisseur ':name' a été supprimé avec succès.",
    'Failed to delete supplier. Please try again.' => 'Échec de la suppression du fournisseur. Veuillez réessayer.',

    // Generic
    'Operation completed successfully.' => 'Opération terminée avec succès.',
    'Failed to complete operation: :message' => 'Échec de l\'opération: :message',
    'You do not have permission to perform this action.' => 'Vous n\'avez pas la permission d\'effectuer cette action.',
    'You do not have access to this vessel.' => 'Vous n\'avez pas accès à ce navire.',
    'Invalid item type.' => 'Type d\'élément invalide.',
    'Invalid month.' => 'Mois invalide.',
    'Invalid year.' => 'Année invalide.',
    'File not found.' => 'Fichier introuvable.',
    'Vessel not found.' => 'Navire introuvable.',
    'You do not have permission to view VAT reports.' => 'Vous n\'avez pas la permission de voir les rapports de TVA.',
    'You do not have permission to view audit logs.' => 'Vous n\'avez pas la permission de voir les journaux d\'audit.',
    'You do not have permission to view financial reports.' => 'Vous n\'avez pas la permission de voir les rapports financiers.',
];

