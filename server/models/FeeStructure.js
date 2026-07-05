/**
 * FeeStructure Model
 * Defines the fee amount for a specific category and class in an academic year.
 * Acts as a fee assignment template that payments are recorded against.
 */

const mongoose = require('mongoose');

const feeStructureSchema = new mongoose.Schema(
  {
    // ─── References ───────────────────────────────────────────────────
    category: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'FeeCategory',
      required: [true, 'Fee category reference is required'],
    },

    class: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Class',
      required: [true, 'Class reference is required'],
    },

    created_by: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
    },

    // ─── Structure Details ────────────────────────────────────────────
    academic_year: {
      type: String,
      required: [true, 'Academic year is required'],
      trim: true,
      maxlength: [20, 'Academic year cannot exceed 20 characters'],
      // e.g. "2024-2025"
    },

    amount: {
      type: mongoose.Schema.Types.Decimal128,
      required: [true, 'Fee amount is required'],
      // Stored as Decimal128 for precision; convert to Number on output
      get: (v) => (v ? parseFloat(v.toString()) : null),
    },

    due_date: {
      type: Date,
      required: [true, 'Due date is required'],
    },

    description: {
      type: String,
      trim: true,
      maxlength: [500, 'Description cannot exceed 500 characters'],
    },

    // ─── Status ──────────────────────────────────────────────────────
    status: {
      type: String,
      enum: {
        values: ['Active', 'Inactive'],
        message: 'Status must be Active or Inactive',
      },
      default: 'Active',
    },
  },
  {
    timestamps: true, // Adds createdAt and updatedAt automatically
    toJSON: { virtuals: true, getters: true },
    toObject: { virtuals: true, getters: true },
  }
);

// ─── Indexes ──────────────────────────────────────────────────────────────────
feeStructureSchema.index({ category: 1, class: 1, academic_year: 1 });
feeStructureSchema.index({ academic_year: 1 });
feeStructureSchema.index({ status: 1 });
feeStructureSchema.index({ due_date: 1 });

// ─── Virtuals ─────────────────────────────────────────────────────────────────
/**
 * Virtual: isOverdue
 * Returns true if the due date has passed
 */
feeStructureSchema.virtual('isOverdue').get(function () {
  if (!this.due_date) return false;
  return new Date() > new Date(this.due_date);
});

// ─── Model Export ─────────────────────────────────────────────────────────────
const FeeStructure = mongoose.model('FeeStructure', feeStructureSchema);

module.exports = FeeStructure;
