/**
 * Class Model
 * Represents a class/section in the institution.
 * Embeds the class_students join table as an array of Student refs.
 */

const mongoose = require('mongoose');

const classSchema = new mongoose.Schema(
  {
    // ─── Class Details ────────────────────────────────────────────────
    class_name: {
      type: String,
      required: [true, 'Class name is required'],
      trim: true,
      maxlength: [100, 'Class name cannot exceed 100 characters'],
    },

    section: {
      type: String,
      trim: true,
      maxlength: [10, 'Section cannot exceed 10 characters'],
    },

    academic_year: {
      type: String,
      required: [true, 'Academic year is required'],
      trim: true,
      maxlength: [20, 'Academic year cannot exceed 20 characters'],
      // e.g. "2024-2025"
    },

    description: {
      type: String,
      trim: true,
      maxlength: [500, 'Description cannot exceed 500 characters'],
    },

    // ─── Class Teacher ────────────────────────────────────────────────
    class_teacher: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      default: null,
    },

    // ─── Embedded Students (replaces class_students join table) ───────
    students: [
      {
        type: mongoose.Schema.Types.ObjectId,
        ref: 'Student',
      },
    ],

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
classSchema.index({ academic_year: 1, class_name: 1, section: 1 });
classSchema.index({ class_teacher: 1 });
classSchema.index({ status: 1 });

// ─── Virtuals ─────────────────────────────────────────────────────────────────
/**
 * Virtual: studentCount
 * Returns the number of students enrolled in this class
 */
classSchema.virtual('studentCount').get(function () {
  return this.students ? this.students.length : 0;
});

/**
 * Virtual: fullName
 * Returns a human-readable class label, e.g. "Class 10 - Section A (2024-2025)"
 */
classSchema.virtual('fullName').get(function () {
  const parts = [this.class_name];
  if (this.section) parts.push(`Section ${this.section}`);
  if (this.academic_year) parts.push(`(${this.academic_year})`);
  return parts.join(' - ');
});

// ─── Model Export ─────────────────────────────────────────────────────────────
const Class = mongoose.model('Class', classSchema);

module.exports = Class;
