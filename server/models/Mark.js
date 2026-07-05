/**
 * Mark Model
 * Stores exam/assessment marks for students per subject.
 * Compound unique index on { student, subject } prevents duplicate entries.
 */

const mongoose = require('mongoose');

const markSchema = new mongoose.Schema(
  {
    // ─── References ───────────────────────────────────────────────────
    student: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Student',
      required: [true, 'Student reference is required'],
    },

    subject: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Subject',
      required: [true, 'Subject reference is required'],
    },

    published_by: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      default: null,
    },

    // ─── Mark Details ─────────────────────────────────────────────────
    marks_obtained: {
      type: Number,
      required: [true, 'Marks obtained is required'],
      min: [0, 'Marks obtained cannot be negative'],
    },

    total_marks: {
      type: Number,
      default: 100,
      min: [1, 'Total marks must be at least 1'],
    },

    // ─── Status & Publication ─────────────────────────────────────────
    status: {
      type: String,
      enum: {
        values: ['Active', 'Inactive'],
        message: 'Status must be Active or Inactive',
      },
      default: 'Active',
    },

    published: {
      type: Boolean,
      default: false,
    },

    published_at: {
      type: Date,
      default: null,
    },
  },
  {
    timestamps: true, // Adds createdAt and updatedAt automatically
    toJSON: { virtuals: true },
    toObject: { virtuals: true },
  }
);

// ─── Indexes ──────────────────────────────────────────────────────────────────
// Compound unique index: one mark record per student per subject
markSchema.index({ student: 1, subject: 1 }, { unique: true });
markSchema.index({ published: 1 });
markSchema.index({ status: 1 });

// ─── Virtuals ─────────────────────────────────────────────────────────────────
/**
 * Virtual: percentage
 * Calculates the percentage score
 */
markSchema.virtual('percentage').get(function () {
  if (!this.total_marks || this.total_marks === 0) return 0;
  return parseFloat(((this.marks_obtained / this.total_marks) * 100).toFixed(2));
});

/**
 * Virtual: grade
 * Returns a letter grade based on percentage
 */
markSchema.virtual('grade').get(function () {
  const pct = this.percentage;
  if (pct >= 90) return 'A+';
  if (pct >= 80) return 'A';
  if (pct >= 70) return 'B';
  if (pct >= 60) return 'C';
  if (pct >= 50) return 'D';
  return 'F';
});

/**
 * Virtual: isPassed
 * Returns true if the student passed (>=40%)
 */
markSchema.virtual('isPassed').get(function () {
  return this.percentage >= 40;
});

// ─── Pre-Save Hook ────────────────────────────────────────────────────────────
/**
 * Ensure marks_obtained does not exceed total_marks.
 */
markSchema.pre('save', function (next) {
  if (this.marks_obtained > this.total_marks) {
    return next(
      new Error(`Marks obtained (${this.marks_obtained}) cannot exceed total marks (${this.total_marks})`)
    );
  }
  // Set published_at if being published for the first time
  if (this.isModified('published') && this.published && !this.published_at) {
    this.published_at = new Date();
  }
  next();
});

// ─── Model Export ─────────────────────────────────────────────────────────────
const Mark = mongoose.model('Mark', markSchema);

module.exports = Mark;
