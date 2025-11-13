<?php

return [
    // Profile
    'Profile updated successfully.'                                                                                           => 'Profile updated successfully.',
    'Failed to update profile: :message'                                                                                      => 'Failed to update profile: :message',
    'Your account has been deleted successfully.'                                                                             => 'Your account has been deleted successfully.',
    'Failed to delete account. Please try again.'                                                                             => 'Failed to delete account. Please try again.',
    'Language updated successfully.'                                                                                          => 'Language updated successfully.',
    'Failed to update language.'                                                                                              => 'Failed to update language.',

    // Marea
    "Marea ':number' has been created successfully."                                                                          => "Marea ':number' has been created successfully.",
    'Failed to create marea: :message'                                                                                        => 'Failed to create marea: :message',
    "Marea ':number' has been updated successfully."                                                                          => "Marea ':number' has been updated successfully.",
    'Failed to update marea: :message'                                                                                        => 'Failed to update marea: :message',
    "Marea ':number' has been deleted successfully."                                                                          => "Marea ':number' has been deleted successfully.",
    'Failed to delete marea: :message'                                                                                        => 'Failed to delete marea: :message',
    "Marea ':number' has been marked as at sea."                                                                              => "Marea ':number' has been marked as at sea.",
    'Failed to mark marea as at sea: :message'                                                                                => 'Failed to mark marea as at sea: :message',
    "Marea ':number' has been marked as returned."                                                                            => "Marea ':number' has been marked as returned.",
    'Failed to mark marea as returned: :message'                                                                              => 'Failed to mark marea as returned: :message',
    "Marea ':number' has been closed."                                                                                        => "Marea ':number' has been closed.",
    'Failed to close marea: :message'                                                                                         => 'Failed to close marea: :message',
    "Marea ':number' has been cancelled."                                                                                     => "Marea ':number' has been cancelled.",
    'Failed to cancel marea: :message'                                                                                        => 'Failed to cancel marea: :message',
    'Transaction has been added to the marea.'                                                                                => 'Movimentation has been added to the marea.',
    'Failed to add transaction: :message'                                                                                     => 'Failed to add movimentation: :message',
    'Transaction has been removed from the marea.'                                                                            => 'Movimentation has been removed from the marea.',
    'Failed to remove transaction: :message'                                                                                  => 'Failed to remove movimentation: :message',

    // Recycle Bin
    ":type ':name' has been restored successfully."                                                                           => ":type ':name' has been restored successfully.",
    'Failed to restore item: :message'                                                                                        => 'Failed to restore item: :message',
    ":type ':name' has been permanently deleted."                                                                             => ":type ':name' has been permanently deleted.",
    'Failed to permanently delete item: :message'                                                                             => 'Failed to permanently delete item: :message',
    'Recycle bin has been emptied. :count item(s) have been permanently deleted.'                                             => 'Recycle bin has been emptied. :count item(s) have been permanently deleted.',
    'Failed to empty recycle bin: :message'                                                                                   => 'Failed to empty recycle bin: :message',

    // Transaction
    "Transaction ':number' has been created successfully."                                                                    => "Movimentation ':number' has been created successfully.",
    'Failed to create transaction: :message'                                                                                  => 'Failed to create movimentation: :message',
    "Transaction ':number' has been updated successfully."                                                                    => "Movimentation ':number' has been updated successfully.",
    'Failed to update transaction: :message'                                                                                  => 'Failed to update movimentation: :message',
    "Transaction ':number' has been deleted successfully."                                                                    => "Movimentation ':number' has been deleted successfully.",
    'Failed to delete transaction: :message'                                                                                  => 'Failed to delete movimentation: :message',

    // Marea - Additional
    "Marea ':number' has been deleted successfully. :count transaction(s) associated with this marea have also been deleted." => "Marea ':number' has been deleted successfully. :count movimentation(s) associated with this marea have also been deleted.",
    'Crew member has been added to the marea.'                                                                                => 'Crew member has been added to the marea.',
    'Failed to add crew member: :message'                                                                                     => 'Failed to add crew member: :message',
    'Crew member is already assigned to this marea.'                                                                          => 'Crew member is already assigned to this marea.',
    'Crew member has been removed from the marea.'                                                                            => 'Crew member has been removed from the marea.',
    'Failed to remove crew member: :message'                                                                                  => 'Failed to remove crew member: :message',
    'Quantity return has been added to the marea.'                                                                            => 'Quantity return has been added to the marea.',
    'Failed to add quantity return: :message'                                                                                 => 'Failed to add quantity return: :message',
    'Quantity return has been removed from the marea.'                                                                        => 'Quantity return has been removed from the marea.',
    'Failed to remove quantity return: :message'                                                                              => 'Failed to remove quantity return: :message',
    'Distribution calculation override has been saved successfully.'                                                          => 'Distribution calculation override has been saved successfully.',
    'Failed to save distribution items: :message'                                                                             => 'Failed to save distribution items: :message',
    'Salary payment has been created successfully.'                                                                           => 'Salary payment has been created successfully.',
    'Failed to create salary payment: :message'                                                                               => 'Failed to create salary payment: :message',

    // Maintenance
    "Maintenance ':number' has been created successfully."                                                                    => "Maintenance ':number' has been created successfully.",
    'Failed to create maintenance: :message'                                                                                  => 'Failed to create maintenance: :message',
    "Maintenance ':number' has been updated successfully."                                                                    => "Maintenance ':number' has been updated successfully.",
    'Failed to update maintenance: :message'                                                                                  => 'Failed to update maintenance: :message',
    "Maintenance ':number' has been deleted successfully."                                                                    => "Maintenance ':number' has been deleted successfully.",
    'Failed to delete maintenance: :message'                                                                                  => 'Failed to delete maintenance: :message',
    "Maintenance ':number' has been finalized."                                                                               => "Maintenance ':number' has been finalized.",
    'Failed to finalize maintenance: :message'                                                                                => 'Failed to finalize maintenance: :message',
    'Transaction has been removed from the maintenance.'                                                                      => 'Movimentation has been removed from the maintenance.',
    'Failed to remove transaction from maintenance: :message'                                                                 => 'Failed to remove movimentation from maintenance: :message',

    // Vessel
    "Vessel ':name' has been created successfully."                                                                           => "Vessel ':name' has been created successfully.",
    'Failed to create vessel. Please try again.'                                                                              => 'Failed to create vessel. Please try again.',
    "Vessel ':name' has been updated successfully."                                                                           => "Vessel ':name' has been updated successfully.",
    'Failed to update vessel. Please try again.'                                                                              => 'Failed to update vessel. Please try again.',
    "Cannot delete vessel ':name' because it has crew members assigned. Please reassign or remove crew members first."        => "Cannot delete vessel ':name' because it has crew members assigned. Please reassign or remove crew members first.",
    "Cannot delete vessel ':name' because it has transactions. Please remove all transactions first."                         => "Cannot delete vessel ':name' because it has movimentations. Please remove all movimentations first.",
    "Vessel ':name' has been deleted successfully."                                                                           => "Vessel ':name' has been deleted successfully.",
    'Failed to delete vessel. Please try again.'                                                                              => 'Failed to delete vessel. Please try again.',

    // Supplier
    "Supplier ':name' has been created successfully."                                                                         => "Supplier ':name' has been created successfully.",
    'Failed to create supplier. Please try again.'                                                                            => 'Failed to create supplier. Please try again.',
    "Supplier ':name' has been updated successfully."                                                                         => "Supplier ':name' has been updated successfully.",
    'Failed to update supplier. Please try again.'                                                                            => 'Failed to update supplier. Please try again.',
    "Cannot delete supplier ':name' because they have transactions. Please remove all transactions first."                    => "Cannot delete supplier ':name' because they have movimentations. Please remove all movimentations first.",
    "Supplier ':name' has been deleted successfully."                                                                         => "Supplier ':name' has been deleted successfully.",
    'Failed to delete supplier. Please try again.'                                                                            => 'Failed to delete supplier. Please try again.',

    // Generic
    'Operation completed successfully.'                                                                                       => 'Operation completed successfully.',
    'Failed to complete operation: :message'                                                                                  => 'Failed to complete operation: :message',
    'You do not have permission to perform this action.'                                                                      => 'You do not have permission to perform this action.',
    'You do not have access to this vessel.'                                                                                  => 'You do not have access to this vessel.',
    'Invalid item type.'                                                                                                      => 'Invalid item type.',
    'Invalid month.'                                                                                                          => 'Invalid month.',
    'Invalid year.'                                                                                                           => 'Invalid year.',
    'File not found.'                                                                                                         => 'File not found.',
    'Vessel not found.'                                                                                                       => 'Vessel not found.',
    'You do not have permission to view VAT reports.'                                                                         => 'You do not have permission to view VAT reports.',
    'You do not have permission to view audit logs.'                                                                          => 'You do not have permission to view audit logs.',
    'You do not have permission to view financial reports.'                                                                   => 'You do not have permission to view financial reports.',

    // Audit Logs
    ':user created :model:identifier'                                                                                         => ':user created :model:identifier',
    ':user deleted :model:identifier'                                                                                         => ':user deleted :model:identifier',
    ':user updated :model:identifier'                                                                                         => ':user updated :model:identifier',
    ':user changed :changes in :model:identifier'                                                                             => ':user changed :changes in :model:identifier',
    'changed :field from \':old\' to \':new\''                                                                                => 'changed :field from \':old\' to \':new\'',
    'updated'                                                                                                                 => 'updated',
    '(empty)'                                                                                                                 => '(empty)',
    'Yes'                                                                                                                     => 'Yes',
    'No'                                                                                                                      => 'No',
];
