import React from 'react';

const Modal = ({ isOpen, onClose, data, title, service }) => {
  if (!isOpen) return null;

  const renderItem = (item) => {
    const commonFields = (
      <>
        <p><strong>ID:</strong> {item.ID}</p>
        <p><strong>Pelapor:</strong> {item.Pelapor || 'N/A'}</p>
        <p><strong>Petugas:</strong> {item.Petugas || 'Belum ada'}</p>
        <p><strong>Pesan:</strong> {item.Pesan || 'Belum ada'}</p>
        {item.Keterangan && item.Keterangan.trim() !== '' && (
          <p><strong>Keterangan Ditunda:</strong> {item.Keterangan}</p>
        )}
        <p><strong>Waktu Masuk:</strong> {item['Waktu Masuk']}</p>
      </>
    );

    const komplainFields = (
      <>
        {item.Lokasi && <p><strong>Lokasi:</strong> {item.Lokasi}</p>}
        {item['Waktu Pengerjaan'] && (
          <>
            <p><strong>Waktu Pengerjaan:</strong> {item['Waktu Pengerjaan']}</p>
            <p><strong>Waktu Selesai:</strong> {item['Waktu Selesai'] || 'Belum ada'}</p>
            <p><strong>Respon Time:</strong> {item['Respon Time']}</p>
          </>
        )}
        {item['Waktu Selesai'] && (
          <p><strong>Durasi Pengerjaan:</strong> {item['Durasi Pengerjaan']}</p>
        )}
      </>
    );

    const updateFields = (
      <>
        {item.Lokasi && <p><strong>Lokasi:</strong> {item.Lokasi}</p>}
        {item['Waktu Mulai'] && <p><strong>Waktu Mulai:</strong> {item['Waktu Mulai']}</p>}
        {item['Waktu Selesai'] && <p><strong>Waktu Selesai:</strong> {item['Waktu Selesai']}</p>}
      </>
    );

    return (
      <div className="mb-4 p-2 border rounded">
        {commonFields}
        {service === 'komplain' ? komplainFields : updateFields}
      </div>
    );
  };

  const renderContent = () => {
    if (!data || typeof data !== 'object' || Object.keys(data).length === 0) {
      return <p className="text-center text-gray-500">Tidak ada data.</p>;
    }

    return Object.entries(data).map(([key, items]) => (
      <React.Fragment key={key}>
        {Array.isArray(items) && items.length > 0 ? (
          items.map((item, index) => (
            <React.Fragment key={index}>
              {renderItem(item)}
            </React.Fragment>
          ))
        ) : (
          <p className="text-center text-gray-500">Tidak ada data.</p>
        )}
      </React.Fragment>
    ));
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center">
      <div className="bg-white p-6 rounded-lg max-w-lg w-full">
        <h2 className="text-xl font-bold mb-4">{title}</h2>
        <div className="max-h-96 overflow-y-auto">
          {renderContent()}
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