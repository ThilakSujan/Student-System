const PDFDocument = require('pdfkit');
const fs = require('fs');
const path = require('path');

exports.generatePDF = (data, type, res) => {
  const doc = new PDFDocument({ margin: 30, size: 'A4' });
  
  res.setHeader('Content-Type', 'application/pdf');
  res.setHeader('Content-Disposition', `attachment; filename=${type}-report.pdf`);
  doc.pipe(res);

  // Header
  doc.rect(0, 0, doc.page.width, 80).fill('#1e293b');
  doc.fontSize(20).fillColor('#ffffff').text('Tech Vision Institute', 30, 25);
  doc.fontSize(12).fillColor('#cbd5e1').text(`${type.toUpperCase()} REPORT`, 30, 50);
  
  // Date
  doc.fontSize(10).fillColor('#ffffff').text(`Generated: ${new Date().toLocaleDateString()}`, doc.page.width - 150, 25);

  doc.moveDown(4);

  // Table generic generation
  if (!data || data.length === 0) {
    doc.fillColor('#0f172a').text('No data available for this report.', 30, 100);
  } else {
    let y = 100;
    const keys = Object.keys(data[0]);
    const colWidth = (doc.page.width - 60) / keys.length;

    // Table Header
    doc.fillColor('#0f172a').font('Helvetica-Bold');
    keys.forEach((key, i) => {
      doc.text(key.toUpperCase().replace('_', ' '), 30 + (i * colWidth), y, { width: colWidth, lineBreak: false });
    });
    
    y += 20;
    doc.moveTo(30, y).lineTo(doc.page.width - 30, y).strokeColor('#cbd5e1').stroke();
    y += 10;

    // Table Rows
    doc.font('Helvetica').fillColor('#334155');
    data.forEach((row, rowIndex) => {
      if (y > doc.page.height - 50) {
        doc.addPage();
        y = 30;
      }
      
      // Zebra striping
      if (rowIndex % 2 === 0) {
        doc.rect(30, y - 5, doc.page.width - 60, 20).fill('#f8fafc');
        doc.fillColor('#334155');
      }

      keys.forEach((key, i) => {
        let val = row[key];
        if (val instanceof Date) val = val.toLocaleDateString();
        if (val === null || val === undefined) val = '-';
        doc.text(String(val), 30 + (i * colWidth), y, { width: colWidth, lineBreak: false });
      });
      y += 20;
    });
  }

  doc.end();
};

exports.generateReportCardPDF = (student, marks, institute, res) => {
  const doc = new PDFDocument({ margin: 40, size: 'A4' });
  
  res.setHeader('Content-Type', 'application/pdf');
  res.setHeader('Content-Disposition', `attachment; filename=${student.student_name.replace(/\s+/g, '_')}_Report_Card.pdf`);
  doc.pipe(res);

  // Top Header (Dark blue background)
  doc.rect(0, 0, doc.page.width, 100).fill('#1e293b');
  
  doc.fillColor('#ffffff').fontSize(24).font('Helvetica-Bold')
     .text(institute?.institute_name || 'Tech Vision Institute', 40, 30, { align: 'center' });
  
  doc.fontSize(12).font('Helvetica')
     .text(institute?.address || '123 Education St, Tech City', 40, 60, { align: 'center' });

  // Title
  doc.moveDown(3);
  doc.fillColor('#0f172a').fontSize(18).font('Helvetica-Bold')
     .text('STUDENT PROGRESS REPORT', { align: 'center' });
  doc.moveDown(2);

  // Student Info Box
  doc.rect(40, doc.y, doc.page.width - 80, 80).fillAndStroke('#f8fafc', '#cbd5e1');
  doc.fillColor('#0f172a').fontSize(10);
  
  const infoY = doc.y + 10;
  doc.font('Helvetica-Bold').text('Name:', 50, infoY).font('Helvetica').text(student.student_name, 120, infoY);
  doc.font('Helvetica-Bold').text('Department:', 50, infoY + 20).font('Helvetica').text(student.department || 'N/A', 120, infoY + 20);
  doc.font('Helvetica-Bold').text('Date:', doc.page.width / 2 + 20, infoY).font('Helvetica').text(new Date().toLocaleDateString(), doc.page.width / 2 + 80, infoY);
  doc.font('Helvetica-Bold').text('Status:', doc.page.width / 2 + 20, infoY + 20).font('Helvetica').text(student.status, doc.page.width / 2 + 80, infoY + 20);
  
  doc.y = infoY + 90;

  // Marks Table
  const tableTop = doc.y;
  doc.font('Helvetica-Bold').fontSize(12).fillColor('#0f172a').text('Academic Performance', 40, tableTop);
  
  const thY = tableTop + 25;
  doc.rect(40, thY, doc.page.width - 80, 25).fill('#e2e8f0');
  doc.fillColor('#0f172a').fontSize(10);
  doc.text('Subject', 50, thY + 8);
  doc.text('Total Marks', 250, thY + 8);
  doc.text('Marks Obtained', 350, thY + 8);
  doc.text('Grade', 480, thY + 8);

  let trY = thY + 25;
  let grandTotal = 0;
  let totalObtained = 0;

  doc.font('Helvetica').fillColor('#334155');
  
  if (marks && marks.length > 0) {
    marks.forEach((mark, index) => {
      if (index % 2 === 0) doc.rect(40, trY, doc.page.width - 80, 25).fill('#f8fafc');
      doc.fillColor('#334155');
      
      const subjectName = mark.subject ? mark.subject.subject_name : 'Unknown';
      doc.text(subjectName, 50, trY + 8);
      doc.text(mark.total_marks.toString(), 250, trY + 8);
      doc.text(mark.marks_obtained.toString(), 350, trY + 8);
      
      // Calculate grade
      const pct = (mark.marks_obtained / mark.total_marks) * 100;
      let grade = 'F';
      if (pct >= 90) grade = 'A+';
      else if (pct >= 80) grade = 'A';
      else if (pct >= 70) grade = 'B';
      else if (pct >= 60) grade = 'C';
      else if (pct >= 50) grade = 'D';
      
      doc.font('Helvetica-Bold').text(grade, 480, trY + 8).font('Helvetica');
      
      grandTotal += mark.total_marks;
      totalObtained += mark.marks_obtained;
      trY += 25;
    });
  } else {
    doc.text('No marks recorded.', 50, trY + 8);
    trY += 25;
  }

  // Summary Box
  doc.moveDown(2);
  const sumY = trY + 20;
  doc.rect(40, sumY, doc.page.width - 80, 60).fill('#1e293b');
  doc.fillColor('#ffffff').font('Helvetica-Bold').fontSize(12);
  doc.text(`Total Marks: ${totalObtained} / ${grandTotal}`, 60, sumY + 25);
  
  const finalPct = grandTotal > 0 ? ((totalObtained / grandTotal) * 100).toFixed(2) : 0;
  doc.text(`Overall Percentage: ${finalPct}%`, 250, sumY + 25);
  
  let finalGrade = 'F';
  if (finalPct >= 90) finalGrade = 'A+';
  else if (finalPct >= 80) finalGrade = 'A';
  else if (finalPct >= 70) finalGrade = 'B';
  else if (finalPct >= 60) finalGrade = 'C';
  else if (finalPct >= 50) finalGrade = 'D';
  doc.text(`Final Grade: ${finalGrade}`, 450, sumY + 25);

  // Footer Signature
  doc.font('Helvetica').fillColor('#0f172a').fontSize(10);
  doc.text('_______________________', 40, doc.page.height - 100);
  doc.text('Principal Signature', 55, doc.page.height - 80);
  
  doc.text('_______________________', doc.page.width - 200, doc.page.height - 100);
  doc.text('Class Teacher Signature', doc.page.width - 190, doc.page.height - 80);

  doc.end();
};
