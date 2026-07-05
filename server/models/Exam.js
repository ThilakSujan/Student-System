const mongoose = require('mongoose');

const examSchema = new mongoose.Schema(
  {
    name: {
      type: String,
      required: [true, 'Exam name is required'],
      trim: true,
      maxlength: [200, 'Exam name cannot exceed 200 characters'],
    },
    exam_type: {
      type: String,
      enum: ['Unit Test', 'Mid Term', 'Final', 'Assignment', 'Quiz', 'Practical', 'Other'],
      default: 'Final',
    },
    class_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Class',
      default: null,
    },
    subject_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Subject',
      default: null,
    },
    date: {
      type: Date,
      default: null,
    },
    start_time: {
      type: String,
      default: null,
    },
    end_time: {
      type: String,
      default: null,
    },
    duration_minutes: {
      type: Number,
      default: null,
    },
    total_marks: {
      type: Number,
      default: 100,
      min: [1, 'Total marks must be at least 1'],
    },
    pass_marks: {
      type: Number,
      default: 40,
      min: [0, 'Pass marks cannot be negative'],
    },
    venue: {
      type: String,
      trim: true,
      default: null,
    },
    description: {
      type: String,
      trim: true,
      default: null,
    },
    academic_year: {
      type: String,
      trim: true,
      default: null,
    },
    status: {
      type: String,
      enum: ['Scheduled', 'Ongoing', 'Completed', 'Cancelled', 'Postponed'],
      default: 'Scheduled',
    },
    created_by: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      default: null,
    },
  },
  {
    timestamps: { createdAt: 'created_at', updatedAt: 'updated_at' },
  }
);

examSchema.index({ date: 1 });
examSchema.index({ class_id: 1, date: 1 });

const Exam = mongoose.model('Exam', examSchema);
module.exports = Exam;
