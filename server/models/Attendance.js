/**
 * Attendance Model
 * Tracks daily attendance records per student.
 * Compound unique index on { student, date } prevents duplicate entries.
 */

const mongoose = require('mongoose');

const attendanceSchema = new mongoose.Schema(
  {
    // ─── References ───────────────────────────────────────────────────
    student: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Student',
      required: [true, 'Student reference is required'],
    },

    marked_by: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      required: [true, 'Marked-by user reference is required'],
    },

    // ─── Attendance Details ───────────────────────────────────────────
    date: {
      type: Date,
      required: [true, 'Attendance date is required'],
    },

    status: {
      type: String,
      enum: {
        values: ['Present', 'Absent', 'Late', 'Excused'],
        message: 'Status must be Present, Absent, Late, or Excused',
      },
      required: [true, 'Attendance status is required'],
    },

    remarks: {
      type: String,
      trim: true,
      maxlength: [300, 'Remarks cannot exceed 300 characters'],
    },
  },
  {
    timestamps: true, // Adds createdAt and updatedAt automatically
    toJSON: { virtuals: true },
    toObject: { virtuals: true },
  }
);

// ─── Indexes ──────────────────────────────────────────────────────────────────
// Compound unique index: one attendance record per student per day
attendanceSchema.index({ student: 1, date: 1 }, { unique: true });
attendanceSchema.index({ date: 1 });
attendanceSchema.index({ status: 1 });
attendanceSchema.index({ marked_by: 1 });

// ─── Virtuals ─────────────────────────────────────────────────────────────────
/**
 * Virtual: isPresent
 * Returns true if the student was marked Present
 */
attendanceSchema.virtual('isPresent').get(function () {
  return this.status === 'Present';
});

// ─── Model Export ─────────────────────────────────────────────────────────────
const Attendance = mongoose.model('Attendance', attendanceSchema);

module.exports = Attendance;
