import React from 'react';

const Modal = ({ isOpen, onClose, data, title }) => {
  if (!isOpen) return null;

  const renderComplaintItem = (item) => (
    <div className="mb-4 p-2 border rounded">
      <p><strong>ID:</strong> {item.ID}</p>
      <p><strong>Pelapor:</strong> {item.Pelapor || 'N/A'}</p>
      <p><strong>Petugas:</strong> {item.Petugas || 'Belum ada'}</p>
      {item.Lokasi && <p><strong>Lokasi:</strong> {item.Lokasi}</p>}
      <p><strong>Waktu Masuk:</strong> {item['Waktu Masuk']}</p>
      {item['Waktu Pengerjaan'] && (
        <>
          <p><strong>Waktu Pengerjaan:</strong> {item['Waktu Pengerjaan']}</p>
          <p><strong>Waktu Selesai:</strong> {item['Waktu Selesai'] || 'N/A'}</p>
          <p><strong>Respon Time:</strong> {item['Respon Time']}</p>
        </>
      )}
      {item['Waktu Selesai'] && (
        <p><strong>Durasi Pengerjaan:</strong> {item['Durasi Pengerjaan']}</p>
      )}
    </div>
  );

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center">
      <div className="bg-white p-6 rounded-lg max-w-lg w-full">
        <h2 className="text-xl font-bold mb-4">{title}</h2>
        <div className="max-h-96 overflow-y-auto">
          {data && Object.keys(data).length > 0 ? (
            Object.entries(data).map(([status, items]) => (
              <div key={status} className="mb-6">
                {items && items.length > 0 ? (
                  items.map((item, index) => (
                    <React.Fragment key={index}>
                      {renderComplaintItem(item)}
                    </React.Fragment>
                  ))
                ) : (
                  <p>Belum ada data {status}</p>
                )}
              </div>
            ))
          ) : (
            <p className="text-center text-gray-500">Belum ada data.</p>
          )}
        </div>
        <button 
          onClick={onClose}
          className="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full"
        >
          Close
        </button>
      </div>
    </div>
  );
};

export default React.memo(Modal);