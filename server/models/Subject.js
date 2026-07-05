/**
 * Subject Model
 * Represents academic subjects offered by the institution.
 */

const mongoose = require('mongoose');

const subjectSchema = new mongoose.Schema(
  {
    // ─── Subject Details ──────────────────────────────────────────────
    subject_code: {
      type: String,
      required: [true, 'Subject code is required'],
      unique: true,
      trim: true,
      uppercase: true,
      maxlength: [20, 'Subject code cannot exceed 20 characters'],
    },

    subject_name: {
      type: String,
      required: [true, 'Subject name is required'],
      trim: true,
      maxlength: [150, 'Subject name cannot exceed 150 characters'],
    },

    credit_hours: {
      type: Number,
      required: [true, 'Credit hours are required'],
      min: [0, 'Credit hours cannot be negative'],
      max: [20, 'Credit hours cannot exceed 20'],
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
    toJSON: { virtuals: true },
    toObject: { virtuals: true },
  }
);

// ─── Indexes ──────────────────────────────────────────────────────────────────
subjectSchema.index({ status: 1 });
subjectSchema.index({ subject_name: 'text' }); // Full-text search

// ─── Model Export ─────────────────────────────────────────────────────────────
const Subject = mongoose.model('Subject', subjectSchema);

module.exports = Subject;
