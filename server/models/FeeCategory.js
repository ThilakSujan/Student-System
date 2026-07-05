/**
 * FeeCategory Model
 * Represents categories of fees (e.g., Tuition, Transport, Library).
 * is_permanent indicates whether this fee applies every academic year.
 */

const mongoose = require('mongoose');

const feeCategorySchema = new mongoose.Schema(
  {
    // ─── Category Details ─────────────────────────────────────────────
    name: {
      type: String,
      required: [true, 'Fee category name is required'],
      trim: true,
      maxlength: [100, 'Fee category name cannot exceed 100 characters'],
    },

    description: {
      type: String,
      trim: true,
      maxlength: [500, 'Description cannot exceed 500 characters'],
    },

    is_permanent: {
      type: Boolean,
      default: false,
      // true = recurring every year (e.g. tuition), false = one-time
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

    // ─── Audit ────────────────────────────────────────────────────────
    created_by: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
    },
  },
  {
    timestamps: true, // Adds createdAt and updatedAt automatically
    toJSON: { virtuals: true },
    toObject: { virtuals: true },
  }
);

// ─── Indexes ──────────────────────────────────────────────────────────────────
feeCategorySchema.index({ status: 1 });
feeCategorySchema.index({ is_permanent: 1 });
feeCategorySchema.index({ name: 'text' }); // Full-text search on name

// ─── Model Export ─────────────────────────────────────────────────────────────
const FeeCategory = mongoose.model('FeeCategory', feeCategorySchema);

module.exports = FeeCategory;
