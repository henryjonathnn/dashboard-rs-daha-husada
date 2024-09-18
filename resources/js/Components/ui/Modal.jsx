import React from 'react';

const Modal = ({ isOpen, onClose, data, title }) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center">
      <div className="bg-white p-6 rounded-lg max-w-lg w-full">
        <h2 className="text-xl font-bold mb-4">{title}</h2>
        <div className="max-h-96 overflow-y-auto">
          {data.length > 0 ? (
            data.map((item, index) => (
              <div key={index} className="mb-4 p-2 border rounded">
                <p><strong>ID:</strong> {item.id}</p>
                <p><strong>Pelapor:</strong> {item.nama_pelapor || 'N/A'}</p>
                <p><strong>Petugas:</strong> {Array.isArray(item.petugas) ? item.petugas.join(', ') : (item.petugas || 'N/A')}</p>
                {/* Kondisi untuk menampilkan lokasi hanya jika ada */}
                {item.lokasi && <p><strong>Lokasi:</strong> {item.lokasi}</p>}
                <p><strong>Waktu Masuk:</strong> {item.datetime_masuk}</p>
                {item.datetime_pengerjaan && (
                  <>
                    <p><strong>Waktu Pengerjaan:</strong> {item.datetime_pengerjaan}</p>
                    <p><strong>Waktu Selesai:</strong> {item.datetime_selesai}</p>
                    <p><strong>Respon Time:</strong> {item.respon_time}</p>
                  </>
                )}
                {item.datetime_selesai && (
                  <p><strong>Durasi Pengerjaan:</strong> {item.durasi_pengerjaan}</p>
                )}
              </div>
            ))
          ) : (
            <p className="text-center text-gray-500">Belum ada data.</p>
          )}
        </div>
        <button 
          onClick={onClose}
          className="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
        >
          Close
        </button>
      </div>
    </div>
  );
};

export default React.memo(Modal);
