<?php
/**
 * Form Validation Partial
 * Includes form validation CSS and error display styles
 */
?>
<style>
/* Form Validation Styles */
.field-error {
    @apply text-sm text-red-600 mt-1 flex items-center;
}

.field-error::before {
    content: "⚠ ";
    @apply mr-1;
}

.form-hint {
    @apply text-sm text-gray-500 mt-1;
}

/* Error states */
.form-input.border-red-500 {
    @apply border-red-500;
}

.form-input.border-red-500:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.35);
}

.form-input.border-green-500 {
    @apply border-green-500;
}

.form-input.border-green-500:focus {
    border-color: #22c55e;
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.35);
}

/* Success checkmark */
.form-group:has(input:not(:invalid):not(:placeholder-shown)) .field-success {
    display: block;
}

.field-success {
    @apply hidden text-green-600 text-sm mt-1 flex items-center;
}

.field-success::before {
    content: "✓ ";
    @apply mr-1 font-bold;
}

/* Required field indicator */
.form-label .required-indicator {
    @apply text-red-500 ml-1;
}

/* Character counter */
.char-counter {
    @apply text-xs text-gray-500 mt-1 text-right;
}

.char-counter.warning {
    @apply text-yellow-600;
}

.char-counter.error {
    @apply text-red-600;
}

/* Loading state for inputs */
.form-input.loading {
    @apply relative;
}

.form-input.loading::after {
    content: "";
    @apply absolute right-3 top-1/2 -translate-y-1/2;
    width: 16px;
    height: 16px;
    border: 2px solid #e5e7eb;
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

/* Validation icon positions */
.form-group {
    @apply relative;
}

.validation-icon {
    @apply absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none;
}

/* Error icon */
.validation-icon.error {
    @apply text-red-500;
}

/* Success icon */
.validation-icon.success {
    @apply text-green-500;
}
</style>

