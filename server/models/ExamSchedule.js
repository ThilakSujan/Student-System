/**
 * ExamSchedule Model
 * Represents a scheduled exam for a subject and class.
 * Tracks exam type, timing, venue, and current status.
 */

const mongoose = require('mongoose');

const examScheduleSchema = new mongoose.Schema(
  {
    // ─── References ───────────────────────────────────────────────────
    subject: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Subject',
      required: [true, 'Subject reference is required'],
    },

    class: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Class',
      required: [true, 'Class reference is required'],
    },

    created_by: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      required: [true, 'Created-by user reference is required'],
    },

    // ─── Exam Details ─────────────────────────────────────────────────
    exam_title: {
      type: String,
      required: [true, 'Exam title is required'],
      trim: true,
      maxlength: [200, 'Exam title cannot exceed 200 characters'],
    },

    exam_date: {
      type: Date,
      required: [true, 'Exam date is required'],
    },

    start_time: {
      type: String,
      required: [true, 'Start time is required'],
      trim: true,
      // Store as "HH:MM" string for flexibility across timezones
    },

    end_time: {
      type: String,
      required: [true, 'End time is required'],
      trim: true,
      // Store as "HH:MM" string
    },

    venue: {
      type: String,
      trim: true,
      maxlength: [200, 'Venue cannot exceed 200 characters'],
    },

    exam_type: {
      type: String,
      enum: {
        values: ['Internal', 'External', 'Practical', 'Viva', 'Other'],
        message: 'Exam type must be Internal, External, Practical, Viva, or Other',
      },
      required: [true, 'Exam type is required'],
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
        values: ['Scheduled', 'Completed', 'Cancelled'],
        message: 'Status must be Scheduled, Completed, or Cancelled',
      },
      default: 'Scheduled',
    },
  },
  {
    timestamps: true, // Adds createdAt and updatedAt automatically
    toJSON: { virtuals: true },
    toObject: { virtuals: true },
  }
);

// ─── Indexes ──────────────────────────────────────────────────────────────────
examScheduleSchema.index({ exam_date: 1, class: 1 });
examScheduleSchema.index({ subject: 1, class: 1 });
examScheduleSchema.index({ status: 1 });
examScheduleSchema.index({ exam_type: 1 });

// ─── Virtuals ─────────────────────────────────────────────────────────────────
/**
 * Virtual: isUpcoming
 * Returns true if the exam is scheduled and in the future
 */
examScheduleSchema.virtual('isUpcoming').get(function () {
  return this.status === 'Scheduled' && new Date(this.exam_date) > new Date();
});

/**
 * Virtual: isPast
 * Returns true if the exam date has already passed
 */
examScheduleSchema.virtual('isPast').get(function () {
  return new Date(this.exam_date) < new Date();
});

// ─── Model Export ─────────────────────────────────────────────────────────────
const ExamSchedule = mongoose.model('ExamSchedule', examScheduleSchema);

module.exports = ExamSchedule;
